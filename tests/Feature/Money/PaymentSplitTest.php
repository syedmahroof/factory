<?php

use App\Jobs\Merchant\InvestorUpdateJob;
use App\Jobs\Merchant\MerchantUpdateJob;
use App\Jobs\Merchant\PaymentJob;
use App\Models\Merchant;
use App\Models\MerchantInvestor;
use App\Models\MerchantPayment;
use App\Models\MerchantPaymentInvestor;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Money-math characterization tests
|--------------------------------------------------------------------------
|
| These do NOT assert what the fee cascade *should* do. They pin down what it
| currently does, to the cent, so the Vue migration can move code out of model
| boot() hooks and retire selfCreate()/selfUpdate() without silently changing a
| single figure. If one of these fails, behaviour changed — that is the signal.
|
| The chain under test is the one every payment entry point dispatches:
|   PaymentJob -> MerchantUpdateJob -> InvestorUpdateJob
|
| Reference (CLAUDE.md):
|   amount         = payment x share / 100, capped at the investor's balance
|   agent_fee      = amount x agent%
|   management_fee = (amount - agent_fee) x mgmt%
|   net            = amount - agent_fee - management_fee
|   net repays `invested` first (principal); everything after is profit.
|
*/

/**
 * users.user_type_id carries an FK to user_types, so the reference rows have to
 * exist before any actor can be created.
 *
 * Seeded per test rather than relied on: other suites in this project use
 * RefreshDatabase, which runs migrate:fresh and drops whatever was sitting in
 * the schema. DatabaseTransactions then rolls these back again.
 */
beforeEach(function () {
    foreach ([
        UserType::Admin => 'Admin',
        UserType::Investor => 'Investor',
        UserType::Lender => 'Lender',
        UserType::Company => 'Company',
        UserType::Merchant => 'Merchant',
        UserType::OverPayment => 'OverPayment',
        UserType::AgentFee => 'Agent Fee',
        UserType::MerchantFees => 'Merchant Fees',
    ] as $id => $name) {
        // Straight to the table: UserType declares no $fillable, so `id` cannot be
        // mass assigned through the model.
        DB::table('user_types')->updateOrInsert(['id' => $id], ['name' => $name]);
    }
});

/** Build a funded advance with three investors holding 50 / 30 / 20 percent. */
function makeAdvance(array $overrides = []): array
{
    $company = User::create([
        'user_type_id' => UserType::Company,
        'name' => 'Char Test Company',
        'email' => 'char-company-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $merchantUser = User::create([
        'user_type_id' => UserType::Merchant,
        'name' => 'Char Test Merchant',
        'email' => 'char-merchant-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
        'company_id' => $company->id,
    ]);

    // merchants.lender_id is NOT NULL with an FK to users, so it defaults to 0 and
    // trips the constraint unless a real lender is named.
    $lender = User::create([
        'user_type_id' => UserType::Lender,
        'name' => 'Char Test Lender',
        'email' => 'char-lender-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $merchant = Merchant::create(array_merge([
        'user_id' => $merchantUser->id,
        'lender_id' => $lender->id,
        'funded' => 100000,
        'factor_rate' => 1.30,
        'no_of_payment' => 100,
        'funded_date' => '2026-01-01',
        'status_id' => 1,
        'created_by' => $company->id,
        'updated_by' => $company->id,
    ], $overrides));

    $investors = [];

    foreach ([['A', 50000, 10], ['B', 30000, 0], ['C', 20000, 5]] as [$label, $funded, $mgmtFee]) {
        $user = User::create([
            'user_type_id' => UserType::Investor,
            'name' => "Char Investor {$label}",
            'email' => 'char-inv-'.strtolower($label).'-'.uniqid().'@example.test',
            'password' => Hash::make('x'),
            'company_id' => $company->id,
        ]);

        $link = MerchantInvestor::create([
            'company_id' => $company->id,
            'investor_id' => $user->id,
            'merchant_id' => $merchantUser->id,
            'funded' => $funded,
            // What InvestmentUpdateJob would set: principal plus investment fees.
            // Held equal to `funded` here so principal/profit is readable by hand.
            'invested' => $funded,
            'management_fee_percentage' => $mgmtFee,
        ]);

        $investors[$label] = ['user' => $user, 'link' => $link];
    }

    return ['company' => $company, 'merchantUser' => $merchantUser, 'merchant' => $merchant, 'investors' => $investors];
}

it('derives rtr and payment_amount from factor rate and funded', function () {
    $ctx = makeAdvance();
    $merchant = $ctx['merchant']->fresh();

    // Merchant::boot() saving hook: rtr = factor_rate x funded
    expect((float) $merchant->rtr)->toBe(130000.0)
        ->and((float) $merchant->payment_amount)->toBe(1300.0);
});

it('derives each investor share and rtr from the funded split', function () {
    $ctx = makeAdvance();

    // MerchantInvestor::boot() saving hook: share = funded / merchant.funded x 100
    expect((float) $ctx['investors']['A']['link']->fresh()->share)->toBe(50.0)
        ->and((float) $ctx['investors']['B']['link']->fresh()->share)->toBe(30.0)
        ->and((float) $ctx['investors']['C']['link']->fresh()->share)->toBe(20.0)
        // rtr = funded x merchant factor_rate
        ->and((float) $ctx['investors']['A']['link']->fresh()->rtr)->toBe(65000.0)
        ->and((float) $ctx['investors']['B']['link']->fresh()->rtr)->toBe(39000.0)
        ->and((float) $ctx['investors']['C']['link']->fresh()->rtr)->toBe(26000.0);
});

it('splits a payment pro rata and applies the fee cascade', function () {
    $ctx = makeAdvance();

    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => 1300,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => 5,
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    $payment = MerchantPayment::where('merchant_id', $ctx['merchantUser']->id)->firstOrFail();
    $rows = MerchantPaymentInvestor::where('merchant_payment_id', $payment->id)
        ->get()
        ->keyBy('investor_id');

    expect($rows)->toHaveCount(3);

    // Both fees are charged on the investor's share of the payment, independently
    // of each other — the management fee is not reduced by the agent's cut.
    //
    // Investor A: 50% of 1300 = 650
    //   agent_fee      = 650 x 5%  = 32.50
    //   management_fee = 650 x 10% = 65.00
    //   net            = 650 - 32.50 - 65.00 = 552.50
    //   invested 50000 is untouched, so the whole net is principal.
    $a = $rows[$ctx['investors']['A']['user']->id];
    expect((float) $a->amount)->toBe(650.0)
        ->and((float) $a->agent_fee)->toBe(32.5)
        ->and((float) $a->management_fee)->toBe(65.0)
        ->and((float) $a->net_amount)->toBe(552.5)
        ->and((float) $a->principal)->toBe(552.5)
        ->and((float) $a->profit)->toBe(0.0);

    // Investor B: 30% of 1300 = 390, no management fee
    //   agent_fee = 19.50 ; management_fee = 0 ; net = 370.50
    $b = $rows[$ctx['investors']['B']['user']->id];
    expect((float) $b->amount)->toBe(390.0)
        ->and((float) $b->agent_fee)->toBe(19.5)
        ->and((float) $b->management_fee)->toBe(0.0)
        ->and((float) $b->net_amount)->toBe(370.5)
        ->and((float) $b->principal)->toBe(370.5);

    // Investor C: 20% of 1300 = 260
    //   agent_fee = 13 ; management_fee = 260 x 5% = 13 ; net = 234
    $c = $rows[$ctx['investors']['C']['user']->id];
    expect((float) $c->amount)->toBe(260.0)
        ->and((float) $c->agent_fee)->toBe(13.0)
        ->and((float) $c->management_fee)->toBe(13.0)
        ->and((float) $c->net_amount)->toBe(234.0);

    // The split is exhaustive: the three amounts are the whole payment.
    expect(round($rows->sum(fn ($r) => (float) $r->amount), 8))->toBe(1300.0);
});

it('treats net beyond invested as profit, not principal', function () {
    $ctx = makeAdvance();

    // Investor A has invested 50000 and 50% of every payment. Pretend almost all of
    // that principal has already been repaid, so the next payment crosses the line.
    //
    // forceFill, not update(): paid_net_amount / paid_principal / paid_profit are
    // deliberately absent from MerchantInvestor::$fillable because they are roll-up
    // columns the jobs own. A plain update() is silently dropped — which is the
    // guard working, and is why this has to reach past it to stage the fixture.
    $link = $ctx['investors']['A']['link'];
    $link->forceFill([
        'paid_net_amount' => 49900,
        'paid_principal' => 49900,
        'paid_amount' => 49900,
    ])->save();

    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => 1300,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => 0,
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    $payment = MerchantPayment::where('merchant_id', $ctx['merchantUser']->id)->firstOrFail();
    $a = MerchantPaymentInvestor::where('merchant_payment_id', $payment->id)
        ->where('investor_id', $ctx['investors']['A']['user']->id)
        ->firstOrFail();

    // amount 650, no agent fee, management 10% => net 585.
    // Only 100 of principal is left (50000 - 49900), so 100 is principal and the
    // remaining 485 is profit.
    expect((float) $a->net_amount)->toBe(585.0)
        ->and((float) $a->principal)->toBe(100.0)
        ->and((float) $a->profit)->toBe(485.0);
});

it('rolls the payment up onto the merchant and the investor links', function () {
    $ctx = makeAdvance();

    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => 1300,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => 0,
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    MerchantUpdateJob::dispatchSync($ctx['merchantUser']->id);
    InvestorUpdateJob::dispatchSync($ctx['merchantUser']->id, $ctx['company']->id);

    $merchant = $ctx['merchant']->fresh();
    $a = $ctx['investors']['A']['link']->fresh();

    // Roll-ups are job-owned: never hand-written. 1300 of 130000 rtr = 1%.
    expect((float) $merchant->balance)->toBe(128700.0)
        ->and((float) $merchant->completed_percentage)->toBe(1.0)
        ->and((int) $merchant->paid_count)->toBe(1);

    // Investor A took 650 of it; their own rtr is 65000, so also 1%.
    expect((float) $a->paid_amount)->toBe(650.0)
        ->and((float) $a->completed_percentage)->toBe(1.0);
});

it('caps an investor at their balance and redistributes the excess', function () {
    $ctx = makeAdvance();

    // Investor C is nearly paid out: 25900 of a 26000 rtr, leaving 100 of balance.
    // A payment that would otherwise hand them 20% must stop at that 100 and push
    // what is left over onto the investors who still have room.
    $ctx['investors']['C']['link']->forceFill([
        'paid_amount' => 25900,
        'paid_net_amount' => 25900,
        'paid_principal' => 20000,
        'paid_profit' => 5900,
    ])->save();

    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => 1300,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => 0,
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    $payment = MerchantPayment::where('merchant_id', $ctx['merchantUser']->id)->firstOrFail();
    $rows = MerchantPaymentInvestor::where('merchant_payment_id', $payment->id)->get()->keyBy('investor_id');

    // C is capped at their remaining balance rather than their 20% share of 260.
    expect((float) $rows[$ctx['investors']['C']['user']->id]->amount)->toBe(100.0);

    // Nothing is lost: the whole 1300 still lands somewhere.
    expect(round($rows->sum(fn ($r) => (float) $r->amount), 8))->toBe(1300.0);

    // The 160 C could not take went to the investors with room left.
    expect((float) $rows[$ctx['investors']['A']['user']->id]->amount)->toBeGreaterThan(650.0);
});

it('keeps users.liquidity equal to transactions plus net profit on the deals', function () {
    $ctx = makeAdvance();

    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => 1300,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => 0,
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    MerchantUpdateJob::dispatchSync($ctx['merchantUser']->id);
    InvestorUpdateJob::dispatchSync($ctx['merchantUser']->id, $ctx['company']->id);

    $investorA = $ctx['investors']['A']['user'];
    $link = $ctx['investors']['A']['link']->fresh();

    // liquidity = SUM(investor_transactions.amount) + SUM(paid_net_amount - invested).
    // There are no manual transactions here, so it is the deal side alone: A has
    // been paid 585 net against 50000 invested, so the wallet sits deep negative
    // until the principal is repaid. That is the documented shape, not a bug.
    $expected = (float) $link->paid_net_amount - (float) $link->invested;

    expect(round((float) $investorA->fresh()->TotalLiquidity(), 6))
        ->toBe(round($expected, 6));
});

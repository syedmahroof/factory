<?php

use App\Actions\Merchant\PreviewPaymentSplitAction;
use App\Jobs\Merchant\PaymentJob;
use App\Models\Merchant;
use App\Models\MerchantInvestor;
use App\Models\MerchantPayment;
use App\Models\MerchantPaymentInvestor;
use App\Models\Settings;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Preview / job agreement
|--------------------------------------------------------------------------
|
| The Add Payment screen computes the split itself so the operator can see where
| the money lands before committing. PaymentJob then computes it AGAIN when the
| entry is recorded. Two implementations of the same fee cascade, and nothing in
| the code forces them to agree.
|
| These tests are that force. Each one previews a payment, records the same
| payment, and asserts the figures match to the cent. If the preview and the job
| ever drift, this fails — which is the whole point of writing it down.
|
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
        DB::table('user_types')->updateOrInsert(['id' => $id], ['name' => $name]);
    }
});

/**
 * @param  float  $agentFee  the platform rate, and whether this advance charges it:
 *                           0 means no agent fee, anything else switches it on.
 */
function makePreviewAdvance(float $agentFee = 5): array
{
    // The agent rate is one platform-wide setting now, not a column on the deal.
    Settings::updateOrCreate(
        ['key' => Settings::AgentFeePercentage],
        ['values' => (string) $agentFee],
    );

    $company = User::create([
        'user_type_id' => UserType::Company,
        'name' => 'Preview Co',
        'email' => 'prev-co-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $lender = User::create([
        'user_type_id' => UserType::Lender,
        'name' => 'Preview Lender',
        'email' => 'prev-lender-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $merchantUser = User::create([
        'user_type_id' => UserType::Merchant,
        'name' => 'Preview Merchant',
        'email' => 'prev-merch-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
        'company_id' => $company->id,
    ]);

    $merchant = Merchant::create([
        'user_id' => $merchantUser->id,
        'lender_id' => $lender->id,
        'funded' => 100000,
        'factor_rate' => 1.30,
        'no_of_payment' => 100,
        'funded_date' => '2026-01-01',
        'status_id' => $agentFee > 0 ? Merchant::Collections : 1,
        // Whether the deal charges the agent fee is its own switch; Collections is
        // only what allows it to be turned on.
        'agent_fee_enabled' => $agentFee > 0,
        'created_by' => $company->id,
        'updated_by' => $company->id,
    ]);

    foreach ([['A', 50000, 10], ['B', 30000, 0], ['C', 20000, 5]] as [$label, $funded, $mgmt]) {
        $user = User::create([
            'user_type_id' => UserType::Investor,
            'name' => "Preview Investor {$label}",
            'email' => 'prev-inv-'.strtolower($label).'-'.uniqid().'@example.test',
            'password' => Hash::make('x'),
            'company_id' => $company->id,
        ]);

        MerchantInvestor::create([
            'company_id' => $company->id,
            'investor_id' => $user->id,
            'merchant_id' => $merchantUser->id,
            'funded' => $funded,
            'invested' => $funded,
            'management_fee_percentage' => $mgmt,
        ]);
    }

    return ['company' => $company, 'merchantUser' => $merchantUser, 'merchant' => $merchant];
}

/** Record a payment and read back what the job actually wrote, per investor. */
function recordAndRead(array $ctx, float $amount): array
{
    PaymentJob::dispatchSync([
        'merchant_id' => $ctx['merchantUser']->id,
        'date' => '2026-02-01',
        'amount' => $amount,
        'payment_mode_id' => MerchantPayment::PaymentModeManual,
        'agent_fee_percentage' => $ctx['merchant']->fresh()->agentFeePercentage(),
        'created_by' => $ctx['company']->id,
        'updated_by' => $ctx['company']->id,
    ]);

    $payment = MerchantPayment::where('merchant_id', $ctx['merchantUser']->id)
        ->orderByDesc('id')->firstOrFail();

    return MerchantPaymentInvestor::where('merchant_payment_id', $payment->id)
        ->get()
        ->keyBy('investor_id')
        ->map(fn ($row) => [
            'amount' => round((float) $row->amount, 8),
            'agent_fee' => round((float) $row->agent_fee, 8),
            'management_fee' => round((float) $row->management_fee, 8),
            'net_amount' => round((float) $row->net_amount, 8),
        ])
        ->all();
}

it('previews the same figures the job writes on an ordinary payment', function () {
    $ctx = makePreviewAdvance();

    $preview = app(PreviewPaymentSplitAction::class)->handle($ctx['merchant'], 1300);
    $actual = recordAndRead($ctx, 1300);

    foreach ($preview['rows'] as $row) {
        expect($actual)->toHaveKey($row['investor_id']);

        $written = $actual[$row['investor_id']];

        expect(round($row['amount'], 8))->toBe($written['amount'])
            ->and(round($row['agent_fee'], 8))->toBe($written['agent_fee'])
            ->and(round($row['management_fee'], 8))->toBe($written['management_fee'])
            ->and(round($row['net_amount'], 8))->toBe($written['net_amount']);
    }

    // And the previewed totals are the totals actually recorded.
    expect(round($preview['totals']['amount'], 8))
        ->toBe(round(array_sum(array_column($actual, 'amount')), 8))
        ->and(round($preview['totals']['net_amount'], 8))
        ->toBe(round(array_sum(array_column($actual, 'net_amount')), 8));
});

it('previews the same figures when an investor is capped and the excess redistributes', function () {
    $ctx = makePreviewAdvance(0);

    // Leave investor C almost paid out, so the split has to cap them and push the
    // remainder onto the others — the branch most likely to drift between the two
    // implementations.
    $c = MerchantInvestor::where('merchant_id', $ctx['merchantUser']->id)
        ->orderBy('share')->first();

    $c->forceFill([
        'paid_amount' => 25900,
        'paid_net_amount' => 25900,
        'paid_principal' => 20000,
        'paid_profit' => 5900,
    ])->save();

    $preview = app(PreviewPaymentSplitAction::class)->handle($ctx['merchant']->fresh(), 1300);
    $actual = recordAndRead($ctx, 1300);

    foreach ($preview['rows'] as $row) {
        expect(round($row['amount'], 8))->toBe($actual[$row['investor_id']]['amount']);
    }

    // The capped investor took only what was owed, and nothing was lost overall.
    expect(round($preview['totals']['amount'] + $preview['overpayment'], 8))->toBe(1300.0);
});

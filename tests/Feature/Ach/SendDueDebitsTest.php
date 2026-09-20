<?php

use App\Actions\Ach\SendDueDebitsAction;
use App\Models\ActumRequest;
use App\Models\Bank;
use App\Models\Merchant;
use App\Models\MerchantPaymentTerm;
use App\Models\MerchantPaymentTermDate;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

/*
|--------------------------------------------------------------------------
| Originating the day's ACH debits
|--------------------------------------------------------------------------
|
| An ACH origination is not transactional with our database: the moment the
| gateway answers, it has been asked for money, and no rollback of ours takes
| that back. This action used to wrap each row in a transaction and roll it back
| on failure, which erased the actum_requests row recording the call that had
| just been made — including the Indeterminate row whose only job is to stop a
| second debit after a lost response.
|
| These pin the property that fix depends on: a declined call is still on file
| afterwards, and the next attempt knows about it.
|
*/

beforeEach(function () {
    // The factories reach for other user types as they build their relations, so
    // the whole enum is present rather than the two this test names.
    foreach (range(1, 15) as $type) {
        DB::table('user_types')->updateOrInsert(['id' => $type], ['name' => 'Type '.$type]);
    }

    config()->set('actum.parent_id', 'PID');
    config()->set('actum.sub_id', 'SID');
    config()->set('actum.username', 'u');
    config()->set('actum.password', 'p');
    config()->set('actum.syspass', 's');

    $this->admin = User::create([
        'user_type_id' => UserType::Admin,
        'name' => 'ACH Admin',
        'email' => 'achsend-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    $this->actingAs($this->admin);

    $merchantUser = User::create([
        'user_type_id' => UserType::Merchant,
        'name' => 'Declining Merchant',
        'email' => 'achmerch-'.uniqid().'@example.test',
        'password' => Hash::make('x'),
    ]);

    /*
     * Built by hand rather than by factory: MerchantFactory still writes a
     * `source_id` the merchants table no longer has, and this test has no stake
     * in the rest of what it fills in.
     */
    $this->merchant = Merchant::create([
        'user_id' => $merchantUser->id,
        'lender_id' => $this->admin->id,
        'funded_date' => date('Y-m-d'),
        'funded' => 10000,
        'factor_rate' => 1.5,
        'no_of_payment' => 10,
        'management_fee_percentage' => 0,
        'agent_fee_percentage' => 0,
        'status_id' => 1,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    Bank::create([
        'user_id' => $merchantUser->id,
        'account_holder_name' => 'Declining Merchant',
        'bank_name' => 'Test Bank',
        'routing_number' => '054000030',
        'account_number' => '1234567890',
        'account_type' => 'C',
        'debit' => 1,
        'default_debit' => 1,
        'status_id' => Bank::Active,
    ]);

    // A term date belongs to a schedule; the FK is enforced.
    $term = MerchantPaymentTerm::create([
        'merchant_id' => $merchantUser->id,
        'advance_type' => 'daily_ach',
        'no_of_payment' => 10,
        'no_of_payment_left' => 10,
        'amount' => 150.00,
        'start_date' => date('Y-m-d'),
        'end_date' => date('Y-m-d'),
        'status_id' => 1,
        'created_by' => $this->admin->id,
        'updated_by' => $this->admin->id,
    ]);

    $this->term = MerchantPaymentTermDate::create([
        'merchant_payment_term_id' => $term->id,
        'merchant_id' => $merchantUser->id,
        'amount' => 150.00,
        'status_id' => MerchantPaymentTermDate::NotPaid,
        'date' => date('Y-m-d'),
    ]);

    $this->row = [[
        'term_date_id' => $this->term->id,
        'merchant_id' => $merchantUser->id,
        'merchant_name' => 'Declining Merchant',
        'date' => date('Y-m-d'),
        'fees' => 0.0,
    ]];
});

/** The gateway declining with DMR201 — the over-the-limit code seen in production. */
function declineOverLimit(): void
{
    Http::fake([
        '*' => Http::response("status=declined\nauthcode=DMR201\nreason=Amount is over the per-transaction limit\n"),
    ]);
}

it('keeps the request on file when the gateway declines', function () {
    declineOverLimit();

    $before = ActumRequest::count();

    $result = app(SendDueDebitsAction::class)->handle($this->row);

    expect($result->data['sent_count'])->toBe(0)
        ->and($result->data['failed_count'])->toBe(1);

    // The call was made, so the record of it has to outlive the failure.
    expect(ActumRequest::count())->toBe($before + 1);

    $request = ActumRequest::latest('id')->first();

    expect($request->model)->toBe('MerchantPaymentTermDate')
        ->and((int) $request->model_id)->toBe($this->term->id)
        ->and((float) $request->requested_amount)->toBe(150.00);
});

it('leaves a declined instalment to be dealt with again', function () {
    declineOverLimit();

    app(SendDueDebitsAction::class)->handle($this->row);

    // Nothing went out, so the debit is still owed and still on the register.
    expect((int) $this->term->fresh()->status_id)->toBe(MerchantPaymentTermDate::NotPaid);
});

it('gives a retry its own idempotence key', function () {
    declineOverLimit();

    app(SendDueDebitsAction::class)->handle($this->row);
    app(SendDueDebitsAction::class)->handle($this->row);

    $keys = ActumRequest::where('model', 'MerchantPaymentTermDate')
        ->where('model_id', $this->term->id)
        ->orderBy('id')
        ->pluck('idempotence_key');

    // The suffix counts attempts already on file. Both attempts went out as
    // `...-0` while the first row was being rolled away underneath the second.
    expect($keys)->toHaveCount(2)
        ->and($keys[0])->not->toBe($keys[1]);
});

it('refuses a row whose fees were never saved without originating anything', function () {
    declineOverLimit();

    $row = $this->row;
    $row[0]['fees'] = 25.00;

    $result = app(SendDueDebitsAction::class)->handle($row);

    expect($result->data['failed_count'])->toBe(1)
        ->and($result->data['failed'][0]['error'])->toContain('save the fees before sending');

    // The point of checking first: nothing was asked of the bank.
    expect(ActumRequest::count())->toBe(0)
        ->and((int) $this->term->fresh()->status_id)->toBe(MerchantPaymentTermDate::NotPaid);
});

<?php

namespace App\Actions\Bank;

use App\Actions\ActionResult;
use App\Models\Bank;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

/**
 * Add or update one bank account on a party — a merchant's collection account or
 * an investor's payout account. Both live in the same table, keyed by users.id.
 *
 * Replaces Bank::selfCreate()/selfUpdate(). The duplicate check matches on
 * `account_number_hash` (the blind index) rather than the column itself, because
 * the encrypted value differs on every write and an equality match on it can never
 * hit. Bank::booted() keeps the hash and the last-4 columns in step.
 *
 * BEHAVIOUR CHANGE — default flags are now exclusive. The legacy screen only
 * *warned* that another account already held the default and wrote the flag anyway,
 * so two accounts on one party could both be `default_debit`. Bank::
 * primaryFor() resolves that with `orderByDesc(default)->orderByDesc(id)`, i.e. it
 * picks whichever was added last — meaning an ACH debit could originate against an
 * account nobody chose. Setting a default here clears it on the party's other
 * accounts. Nobody currently has a conflicting pair, so this changes no
 * existing data.
 */
class SaveBankAccountAction
{
    public function handle(User $owner, array $attributes, ?Bank $existing = null): ActionResult
    {
        $accountNumber = (string) ($attributes['account_number'] ?? '');

        $duplicate = Bank::where('user_id', $owner->id)
            ->where('account_number_hash', Bank::blindIndex($accountNumber))
            ->when($existing, fn ($q) => $q->where('id', '!=', $existing->id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'account_number' => 'This account is already on file for this party.',
            ]);
        }

        try {
            $bank = DB::transaction(function () use ($owner, $attributes, $existing) {
                $bank = $existing ?: new Bank;

                $bank->fill([
                    'user_id' => $owner->id,
                    'account_holder_name' => $attributes['account_holder_name'],
                    'bank_name' => $attributes['bank_name'],
                    'account_number' => $attributes['account_number'],
                    'routing_number' => $attributes['routing_number'],
                    'account_type' => $attributes['account_type'] ?? 'C',
                    'status_id' => $attributes['status_id'] ?? Bank::Active,
                    'debit' => $attributes['debit'] ?? 1,
                    'credit' => $attributes['credit'] ?? 0,
                    'default_debit' => $attributes['default_debit'] ?? 0,
                    'default_credit' => $attributes['default_credit'] ?? 0,
                    'verification_note' => $attributes['verification_note'] ?? null,
                ]);

                $bank->save();

                // One default per direction per party.
                foreach (['default_debit', 'default_credit'] as $flag) {
                    if ($bank->{$flag}) {
                        Bank::where('user_id', $owner->id)
                            ->where('id', '!=', $bank->id)
                            ->where($flag, 1)
                            ->update([$flag => 0]);
                    }
                }

                return $bank;
            });

            return ActionResult::success($bank->fresh(), 'successfully saved');
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to save the bank account.', 500);
        }
    }
}

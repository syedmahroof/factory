<?php

namespace App\Actions\Finance;

use App\Models\Account;
use Illuminate\Support\Collection;

/**
 * The trial balance: every active account with what it moved and where it stands.
 *
 * Movement is summed from `journal_lines`, not from `journals.total_debit`. The
 * latter is the whole entry's total across every account it touches — reading it
 * per account gave each account the full value of every entry it appeared in, so
 * a two-line entry counted twice and the balance never balanced.
 */
class GenerateTrialBalance
{
    public function execute(?int $fiscalPeriodId = null): Collection
    {
        $accounts = Account::where('is_active', true)
            ->withSum(['postedLines as debit_total' => function ($q) use ($fiscalPeriodId) {
                $this->inPeriod($q, $fiscalPeriodId);
            }], 'debit')
            ->withSum(['postedLines as credit_total' => function ($q) use ($fiscalPeriodId) {
                $this->inPeriod($q, $fiscalPeriodId);
            }], 'credit')
            ->orderBy('code')
            ->get();

        return $accounts->map(function ($account) {
            $debit = (float) ($account->debit_total ?? 0);
            $credit = (float) ($account->credit_total ?? 0);
            $opening = (float) ($account->opening_balance ?? 0);

            return [
                'account_code' => $account->code,
                'account_name' => $account->name,
                'account_type' => $account->type,
                'opening_balance' => $opening,
                'debit' => $debit,
                'credit' => $credit,
                'closing_balance' => $opening + $debit - $credit,
            ];
        });
    }

    /** A period narrows the report to the entries posted inside it. */
    private function inPeriod($query, ?int $fiscalPeriodId): void
    {
        if ($fiscalPeriodId !== null) {
            $query->whereHas('journal', fn ($j) => $j->where('fiscal_period_id', $fiscalPeriodId));
        }
    }
}

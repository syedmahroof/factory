<?php

namespace App\Actions\Finance;

use App\Models\Account;

/**
 * Profit and loss for the period.
 *
 * Two things were wrong with the previous version. It filtered on `sub_type`, a
 * column `accounts` does not have, so the report was a 500 on every call. And it
 * summed `opening_balance`, which is where an account *started* — a P&L built
 * from opening balances reports the same numbers forever, whatever anyone posts.
 *
 * Both are now taken from posted journal lines, the same source the trial balance
 * reads, so the two reports agree.
 *
 * Cost of sales has no column of its own in this schema; it is identified by the
 * account group the account belongs to. Anything expense-side that is not in such
 * a group counts as operating expense.
 */
class GenerateProfitLoss
{
    /** How a cost-of-sales account group announces itself, absent a dedicated flag. */
    private const COGS_PATTERNS = ['cogs', 'cost of goods', 'cost of sales'];

    /** @return array<string, float> */
    public function execute(?int $fiscalPeriodId = null): array
    {
        $accounts = Account::where('is_active', true)
            ->whereIn('type', ['revenue', 'expense'])
            ->with('accountGroup:id,code,name')
            ->withSum(['postedLines as debit_total' => fn ($q) => $this->inPeriod($q, $fiscalPeriodId)], 'debit')
            ->withSum(['postedLines as credit_total' => fn ($q) => $this->inPeriod($q, $fiscalPeriodId)], 'credit')
            ->get();

        $revenue = 0.0;
        $cogs = 0.0;
        $operatingExpenses = 0.0;

        foreach ($accounts as $account) {
            $debit = (float) ($account->debit_total ?? 0);
            $credit = (float) ($account->credit_total ?? 0);

            if ($account->type === 'revenue') {
                // Revenue is a credit balance, so credits less debits is income.
                $revenue += $credit - $debit;

                continue;
            }

            $amount = $debit - $credit;

            $this->isCostOfSales($account)
                ? $cogs += $amount
                : $operatingExpenses += $amount;
        }

        $grossProfit = $revenue - $cogs;
        $operatingIncome = $grossProfit - $operatingExpenses;

        return [
            'revenue' => round($revenue, 2),
            'cost_of_goods_sold' => round($cogs, 2),
            'gross_profit' => round($grossProfit, 2),
            'operating_expenses' => round($operatingExpenses, 2),
            'operating_income' => round($operatingIncome, 2),
            'net_income' => round($operatingIncome, 2),
        ];
    }

    private function isCostOfSales(Account $account): bool
    {
        $haystack = strtolower(trim(($account->accountGroup?->code ?? '').' '.($account->accountGroup?->name ?? '')));

        if ($haystack === '') {
            return false;
        }

        foreach (self::COGS_PATTERNS as $pattern) {
            if (str_contains($haystack, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /** A period narrows the report to the entries posted inside it. */
    private function inPeriod($query, ?int $fiscalPeriodId): void
    {
        if ($fiscalPeriodId !== null) {
            $query->whereHas('journal', fn ($j) => $j->where('fiscal_period_id', $fiscalPeriodId));
        }
    }
}

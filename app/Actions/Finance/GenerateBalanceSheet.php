<?php

namespace App\Actions\Finance;

use App\Models\Account;
use Illuminate\Support\Facades\DB;

class GenerateBalanceSheet
{
    public function execute(): array
    {
        $assets = Account::where('type', 'asset')->where('is_active', true)->sum(DB::raw('opening_balance'));
        $liabilities = Account::where('type', 'liability')->where('is_active', true)->sum(DB::raw('opening_balance'));
        $equity = Account::where('type', 'equity')->where('is_active', true)->sum(DB::raw('opening_balance'));
        $revenue = Account::where('type', 'revenue')->where('is_active', true)->sum(DB::raw('opening_balance'));
        $expense = Account::where('type', 'expense')->where('is_active', true)->sum(DB::raw('opening_balance'));
        $netIncome = $revenue - $expense;

        return [
            'assets' => ['total' => $assets],
            'liabilities' => ['total' => $liabilities],
            'equity' => ['total' => $equity + $netIncome],
            'net_income' => $netIncome,
            'total_liabilities_and_equity' => $liabilities + $equity + $netIncome,
        ];
    }
}

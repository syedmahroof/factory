<?php

namespace Database\Seeders;

use App\Models\BankReconciliation;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BankReconciliationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('bank_reconciliations')) {
            $this->command?->warn('Skipping BankReconciliationSeeder: table "bank_reconciliations" does not exist in the current schema.');

            return;
        }

        BankReconciliation::create([
            'bank_account_id' => 1,
            'statement_date' => now()->toDateString(),
            'statement_balance' => 84950,
            'book_balance' => 84950,
            'difference' => 0,
            'status' => 'reconciled',
        ]);
    }
}

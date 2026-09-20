<?php

namespace Database\Seeders;

use App\Models\BankAccount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BankAccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('bank_accounts')) {
            $this->command?->warn('Skipping BankAccountSeeder: table "bank_accounts" does not exist in the current schema.');

            return;
        }

        BankAccount::create([
            'account_id' => 1,
            'bank_name' => 'First National Bank',
            'account_number' => '000123456789',
            'routing_number' => '072000326',
            'currency' => 'USD',
            'balance' => 85000,
            'is_active' => true,
        ]);
    }
}

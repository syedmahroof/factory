<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AccountSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('accounts')) {
            $this->command?->warn('Skipping AccountSeeder: table "accounts" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping AccountSeeder: company FAC001 not found.');

            return;
        }

        $assets = AccountGroup::where('code', '1000')->first();
        $revenue = AccountGroup::where('code', '4000')->first();

        foreach ([
            ['code' => '1010', 'name' => 'Cash', 'type' => 'asset', 'group' => $assets, 'is_bank_account' => true],
            ['code' => '1200', 'name' => 'Inventory', 'type' => 'asset', 'group' => $assets, 'is_bank_account' => false],
            ['code' => '4010', 'name' => 'Sales Revenue', 'type' => 'revenue', 'group' => $revenue, 'is_bank_account' => false],
        ] as $account) {
            Account::firstOrCreate(['company_id' => $company->id, 'code' => $account['code']], [
                'name' => $account['name'],
                'description' => $account['name'],
                'account_group_id' => $account['group']?->id,
                'type' => $account['type'],
                'is_control_account' => false,
                'is_bank_account' => $account['is_bank_account'],
                'is_cash_account' => $account['code'] === '1010',
                'currency' => 'USD',
                'opening_balance' => 0,
                'is_active' => true,
            ]);
        }
    }
}

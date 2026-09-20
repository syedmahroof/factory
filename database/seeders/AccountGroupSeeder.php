<?php

namespace Database\Seeders;

use App\Models\AccountGroup;
use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AccountGroupSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('account_groups')) {
            $this->command?->warn('Skipping AccountGroupSeeder: table "account_groups" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping AccountGroupSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => '1000', 'name' => 'Assets', 'type' => 'asset'],
            ['code' => '2000', 'name' => 'Liabilities', 'type' => 'liability'],
            ['code' => '3000', 'name' => 'Equity', 'type' => 'equity'],
            ['code' => '4000', 'name' => 'Revenue', 'type' => 'revenue'],
            ['code' => '5000', 'name' => 'Expenses', 'type' => 'expense'],
        ] as $group) {
            AccountGroup::firstOrCreate(['code' => $group['code']], array_merge($group, [
                'company_id' => $company->id,
                'level' => 1,
                'is_control_account' => false,
            ]));
        }
    }
}

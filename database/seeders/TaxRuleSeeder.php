<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\TaxRule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TaxRuleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tax_rules')) {
            $this->command?->warn('Skipping TaxRuleSeeder: table "tax_rules" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $account = Account::where('code', '1200')->first();
        if (! $company || ! $account) {
            $this->command?->warn('Skipping TaxRuleSeeder: company or account 1200 not found.');

            return;
        }

        TaxRule::firstOrCreate(['company_id' => $company->id, 'code' => 'TX-0001'], [
            'name' => 'Standard Sales Tax',
            'type' => 'sales_tax',
            'rate' => 5.00,
            'account_id' => $account->id,
            'effective_from' => now()->startOfYear()->toDateString(),
            'is_active' => true,
        ]);
    }
}

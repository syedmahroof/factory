<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('customers')) {
            $this->command?->warn('Skipping CustomerSeeder: table "customers" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping CustomerSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'CUST001', 'name' => 'Industrial Solutions Inc.', 'credit_terms' => 'NET30'],
            ['code' => 'CUST002', 'name' => 'Global Auto Parts', 'credit_terms' => 'NET45'],
        ] as $customer) {
            Customer::firstOrCreate(['code' => $customer['code']], array_merge($customer, [
                'company_id' => $company->id,
                'legal_name' => $customer['name'],
                'address' => '300 Customer Avenue',
                'city' => 'Cleveland',
                'state' => 'OH',
                'country' => 'US',
                'postal_code' => '44101',
                'phone' => '+1 216 555 0100',
                'email' => 'sales@'.strtolower(str_replace([' ', '.'], '', $customer['name'])).'.example.com',
                'credit_limit' => 50000,
                'outstanding_balance' => 0,
                'currency' => 'USD',
                'status' => 'active',
            ]));
        }
    }
}

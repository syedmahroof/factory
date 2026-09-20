<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Supplier;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('suppliers')) {
            $this->command?->warn('Skipping SupplierSeeder: table "suppliers" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping SupplierSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'SUP001', 'name' => 'SteelCorp International', 'payment_terms' => 'NET30'],
            ['code' => 'SUP002', 'name' => 'AluTech Materials', 'payment_terms' => 'NET45'],
        ] as $supplier) {
            Supplier::firstOrCreate(['code' => $supplier['code']], array_merge($supplier, [
                'company_id' => $company->id,
                'legal_name' => $supplier['name'],
                'address' => '500 Supplier Drive',
                'city' => 'Pittsburgh',
                'state' => 'PA',
                'country' => 'US',
                'postal_code' => '15201',
                'phone' => '+1 412 555 0100',
                'email' => strtolower(str_replace(' ', '.', $supplier['name'])).'@example.com',
                'currency' => 'USD',
                'rating' => 4.5,
                'status' => 'active',
                'credit_limit' => 100000,
            ]));
        }
    }
}

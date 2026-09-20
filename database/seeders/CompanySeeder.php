<?php

namespace Database\Seeders;

use App\Models\Company;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CompanySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('companies')) {
            $this->command?->warn('Skipping CompanySeeder: table "companies" not found.');

            return;
        }

        Company::firstOrCreate(['code' => 'FAC001'], [
            'name' => 'Advanced Manufacturing Corp.',
            'legal_name' => 'Advanced Manufacturing Corp. LLC',
            'address' => '100 Industrial Parkway',
            'city' => 'Detroit',
            'state' => 'MI',
            'country' => 'US',
            'postal_code' => '48201',
            'phone' => '+1 313 555 0100',
            'email' => 'info@amc.example.com',
            'website' => 'https://amc.example.com',
            'tax_id' => 'US-12-3456789',
            'registration_number' => 'REG-0001',
            'currency' => 'USD',
            'fiscal_year_start_month' => '01',
            'is_active' => true,
        ]);
    }
}

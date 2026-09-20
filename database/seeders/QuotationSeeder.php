<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QuotationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quotations')) {
            $this->command?->warn('Skipping QuotationSeeder: table "quotations" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $customer = Customer::where('code', 'CUST001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $customer || ! $admin) {
            $this->command?->warn('Skipping QuotationSeeder: required records not found.');

            return;
        }

        Quotation::firstOrCreate(['number' => 'Q-0001'], [
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'quotation_date' => now()->subDays(12)->toDateString(),
            'valid_until' => now()->addDays(18)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 4500,
            'tax_amount' => 0,
            'discount_amount' => 0,
            'total_amount' => 4500,
            'terms_and_conditions' => 'Net 30 days from invoice date.',
            'status' => 'sent',
            'revision' => 1,
        ]);
    }
}

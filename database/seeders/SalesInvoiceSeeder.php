<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\SalesInvoice;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SalesInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('sales_invoices')) {
            $this->command?->warn('Skipping SalesInvoiceSeeder: table "sales_invoices" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $so = SalesOrder::where('number', 'SO-0001')->first();
        $customer = Customer::where('code', 'CUST001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $so || ! $customer || ! $admin) {
            $this->command?->warn('Skipping SalesInvoiceSeeder: required records not found.');

            return;
        }

        SalesInvoice::firstOrCreate(['number' => 'INV-0001'], [
            'company_id' => $company->id,
            'sales_order_id' => $so->id,
            'customer_id' => $customer->id,
            'created_by' => $admin->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 4500,
            'tax_amount' => 0,
            'total_amount' => 4650,
            'amount_paid' => 0,
            'balance_due' => 4650,
            'notes' => 'Invoice for SO-0001.',
            'status' => 'sent',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\CustomerInvoice;
use App\Models\SalesOrder;
use App\Models\Shipment;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CustomerInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('customer_invoices')) {
            $this->command?->warn('Skipping CustomerInvoiceSeeder: table "customer_invoices" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $customer = Customer::where('code', 'CUST001')->first();
        $so = SalesOrder::where('number', 'SO-0001')->first();
        $shipment = Shipment::where('number', 'SH-0001')->first();
        if (! $company || ! $customer || ! $so) {
            $this->command?->warn('Skipping CustomerInvoiceSeeder: required records not found.');

            return;
        }

        $invoice = CustomerInvoice::firstOrNew(['number' => 'CI-0001']);
        if (! $invoice->exists) {
            $invoice->uuid = (string) Str::uuid();
        }
        $invoice->fill([
            'company_id' => $company->id,
            'customer_id' => $customer->id,
            'sales_order_id' => $so->id,
            'shipment_id' => $shipment?->id,
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 4500,
            'tax_amount' => 0,
            'total_amount' => 4650,
            'amount_paid' => 0,
            'balance_due' => 4650,
            'status' => 'sent',
            'notes' => 'Invoice generated from SO-0001.',
        ])->save();
    }
}

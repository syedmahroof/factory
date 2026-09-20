<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SupplierInvoiceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('supplier_invoices')) {
            $this->command?->warn('Skipping SupplierInvoiceSeeder: table "supplier_invoices" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $supplier = Supplier::where('code', 'SUP001')->first();
        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $gr = GoodsReceipt::where('number', 'GR-0001')->first();
        if (! $company || ! $supplier || ! $po || ! $gr) {
            $this->command?->warn('Skipping SupplierInvoiceSeeder: required records not found.');

            return;
        }

        $invoice = SupplierInvoice::firstOrNew(['number' => 'SI-0001']);
        if (! $invoice->exists) {
            $invoice->uuid = (string) Str::uuid();
        }
        $invoice->fill([
            'company_id' => $company->id,
            'supplier_id' => $supplier->id,
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr->id,
            'supplier_invoice_number' => 'SC-INV-8842',
            'invoice_date' => now()->toDateString(),
            'due_date' => now()->addDays(30)->toDateString(),
            'currency' => 'USD',
            'subtotal' => 2090,
            'tax_amount' => 0,
            'total_amount' => 2090,
            'amount_paid' => 0,
            'balance_due' => 2090,
            'status' => 'approved',
            'notes' => 'Invoice received from SteelCorp.',
        ])->save();
    }
}

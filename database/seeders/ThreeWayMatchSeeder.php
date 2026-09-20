<?php

namespace Database\Seeders;

use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\SupplierInvoice;
use App\Models\ThreeWayMatch;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ThreeWayMatchSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('three_way_matches')) {
            $this->command?->warn('Skipping ThreeWayMatchSeeder: table "three_way_matches" not found.');

            return;
        }

        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $gr = GoodsReceipt::where('number', 'GR-0001')->first();
        $invoice = SupplierInvoice::where('number', 'SI-0001')->first();
        if (! $po || ! $gr || ! $invoice) {
            $this->command?->warn('Skipping ThreeWayMatchSeeder: required documents not found.');

            return;
        }

        ThreeWayMatch::firstOrCreate([
            'purchase_order_id' => $po->id,
            'goods_receipt_id' => $gr->id,
            'supplier_invoice_id' => $invoice->id,
        ], [
            'quantity_tolerance' => 2.00,
            'price_tolerance' => 1.00,
            'po_total' => 2090,
            'grn_total' => 2090,
            'invoice_total' => 2090,
            'status' => 'matched',
        ]);
    }
}

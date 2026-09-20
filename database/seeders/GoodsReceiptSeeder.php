<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\GoodsReceipt;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GoodsReceiptSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('goods_receipts')) {
            $this->command?->warn('Skipping GoodsReceiptSeeder: table "goods_receipts" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $supplier = Supplier::where('code', 'SUP001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();
        if (! $company || ! $po || ! $supplier || ! $warehouse) {
            $this->command?->warn('Skipping GoodsReceiptSeeder: required records not found.');

            return;
        }

        GoodsReceipt::firstOrCreate(['number' => 'GR-0001'], [
            'company_id' => $company->id,
            'purchase_order_id' => $po->id,
            'supplier_id' => $supplier->id,
            'warehouse_id' => $warehouse->id,
            'receipt_date' => now()->toDateString(),
            'notes' => 'Delivered via SteelCorp freight.',
            'status' => 'received',
        ]);
    }
}

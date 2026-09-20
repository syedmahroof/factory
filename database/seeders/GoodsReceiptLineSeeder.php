<?php

namespace Database\Seeders;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class GoodsReceiptLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('goods_receipt_lines')) {
            $this->command?->warn('Skipping GoodsReceiptLineSeeder: table "goods_receipt_lines" not found.');

            return;
        }

        $gr = GoodsReceipt::where('number', 'GR-0001')->first();
        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $rm002 = Item::where('code', 'RM002')->first();
        if (! $gr || ! $po || ! $kg || ! $rm001 || ! $rm002) {
            $this->command?->warn('Skipping GoodsReceiptLineSeeder: required records not found.');

            return;
        }

        foreach ([
            ['item' => $rm001, 'quantity' => 500],
            ['item' => $rm002, 'quantity' => 200],
        ] as $line) {
            $poLine = PurchaseOrderLine::where('purchase_order_id', $po->id)
                ->where('item_id', $line['item']->id)->first();

            GoodsReceiptLine::firstOrCreate([
                'goods_receipt_id' => $gr->id,
                'item_id' => $line['item']->id,
            ], [
                'purchase_order_line_id' => $poLine?->id,
                'quantity' => $line['quantity'],
                'accepted_quantity' => $line['quantity'],
                'rejected_quantity' => 0,
                'uom_id' => $kg->id,
            ]);
        }
    }
}

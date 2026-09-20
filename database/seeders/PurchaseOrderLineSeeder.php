<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PurchaseOrderLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('purchase_order_lines')) {
            $this->command?->warn('Skipping PurchaseOrderLineSeeder: table "purchase_order_lines" not found.');

            return;
        }

        $po = PurchaseOrder::where('number', 'PO-0001')->first();
        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $rm002 = Item::where('code', 'RM002')->first();
        if (! $po || ! $kg || ! $rm001 || ! $rm002) {
            $this->command?->warn('Skipping PurchaseOrderLineSeeder: required PO/items/UOM not found.');

            return;
        }

        foreach ([
            ['line_number' => 10, 'item' => $rm001, 'quantity' => 500, 'unit_price' => 2.50, 'line_total' => 1250],
            ['line_number' => 20, 'item' => $rm002, 'quantity' => 200, 'unit_price' => 4.20, 'line_total' => 840],
        ] as $line) {
            PurchaseOrderLine::firstOrCreate([
                'purchase_order_id' => $po->id,
                'line_number' => $line['line_number'],
            ], [
                'item_id' => $line['item']->id,
                'quantity' => $line['quantity'],
                'received_quantity' => 0,
                'invoiced_quantity' => 0,
                'uom_id' => $kg->id,
                'unit_price' => $line['unit_price'],
                'tax_rate' => 0,
                'discount_rate' => 0,
                'line_total' => $line['line_total'],
                'required_date' => now()->addDays(9)->toDateString(),
                'status' => 'pending',
            ]);
        }
    }
}

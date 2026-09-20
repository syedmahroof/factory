<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SalesOrderLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('sales_order_lines')) {
            $this->command?->warn('Skipping SalesOrderLineSeeder: table "sales_order_lines" not found.');

            return;
        }

        $so = SalesOrder::where('number', 'SO-0001')->first();
        $ea = Uom::where('code', 'EA')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        if (! $so || ! $ea || ! $fg001) {
            $this->command?->warn('Skipping SalesOrderLineSeeder: required records not found.');

            return;
        }

        SalesOrderLine::firstOrCreate([
            'sales_order_id' => $so->id,
            'line_number' => 10,
        ], [
            'item_id' => $fg001->id,
            'quantity' => 100,
            'picked_quantity' => 0,
            'shipped_quantity' => 0,
            'invoiced_quantity' => 0,
            'uom_id' => $ea->id,
            'unit_price' => 45.00,
            'discount_rate' => 0,
            'tax_rate' => 0,
            'line_total' => 4500,
            'requested_date' => now()->addDays(9)->toDateString(),
            'status' => 'pending',
        ]);
    }
}

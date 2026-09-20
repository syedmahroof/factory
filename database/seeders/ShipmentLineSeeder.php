<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ShipmentLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('shipment_lines')) {
            $this->command?->warn('Skipping ShipmentLineSeeder: table "shipment_lines" not found.');

            return;
        }

        $shipment = Shipment::where('number', 'SH-0001')->first();
        $so = SalesOrder::where('number', 'SO-0001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        if (! $shipment || ! $so || ! $fg001) {
            $this->command?->warn('Skipping ShipmentLineSeeder: required records not found.');

            return;
        }

        $soLine = SalesOrderLine::where('sales_order_id', $so->id)->where('line_number', 10)->first();

        ShipmentLine::firstOrCreate([
            'shipment_id' => $shipment->id,
            'sales_order_line_id' => $soLine?->id,
        ], [
            'item_id' => $fg001->id,
            'quantity' => 100,
        ]);
    }
}

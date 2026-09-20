<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\SerialGenealogy;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SerialGenealogySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('serial_genealogies')) {
            $this->command?->warn('Skipping SerialGenealogySeeder: table "serial_genealogies" not found.');

            return;
        }

        $fg001 = Item::where('code', 'FG001')->first();
        $mo = ProductionOrder::where('number', 'MO-0001')->first();
        if (! $fg001 || ! $mo) {
            $this->command?->warn('Skipping SerialGenealogySeeder: item FG001 or production order not found.');

            return;
        }

        SerialGenealogy::firstOrCreate([
            'parent_serial_number' => 'SN-RM001-0001',
            'child_serial_number' => 'SN-FG001-0001',
        ], [
            'production_order_id' => $mo->id,
            'item_id' => $fg001->id,
            'quantity' => 1,
        ]);
    }
}

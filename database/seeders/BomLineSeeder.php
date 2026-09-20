<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\BomLine;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BomLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('bom_lines')) {
            $this->command?->warn('Skipping BomLineSeeder: table "bom_lines" not found.');

            return;
        }

        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $rm002 = Item::where('code', 'RM002')->first();

        $bomFg1 = Bom::whereHas('item', fn ($q) => $q->where('code', 'FG001'))->first();
        if (! $kg || ! $rm001 || ! $rm002 || ! $bomFg1) {
            $this->command?->warn('Skipping BomLineSeeder: required BOM/items/UOM not found.');

            return;
        }

        foreach ([
            ['sequence' => 10, 'item' => $rm001, 'quantity' => 2.000000, 'cost' => 5.00],
            ['sequence' => 20, 'item' => $rm002, 'quantity' => 0.500000, 'cost' => 2.10],
        ] as $line) {
            BomLine::firstOrCreate([
                'bom_id' => $bomFg1->id,
                'sequence' => $line['sequence'],
            ], [
                'item_id' => $line['item']->id,
                'quantity' => $line['quantity'],
                'uom_id' => $kg->id,
                'scrap_factor' => 0,
                'cost' => $line['cost'],
                'is_optional' => false,
                'is_co_product' => false,
                'is_by_product' => false,
                'is_active' => true,
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Formula;
use App\Models\FormulaLine;
use App\Models\Item;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class FormulaLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('formula_lines')) {
            $this->command?->warn('Skipping FormulaLineSeeder: table "formula_lines" not found.');

            return;
        }

        $formula = Formula::whereHas('item', fn ($q) => $q->where('code', 'FG001'))->first();
        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $rm002 = Item::where('code', 'RM002')->first();
        if (! $formula || ! $kg || ! $rm001 || ! $rm002) {
            $this->command?->warn('Skipping FormulaLineSeeder: required formula/items/UOM not found.');

            return;
        }

        foreach ([
            ['sequence' => 10, 'item' => $rm001, 'quantity' => 2.0000, 'type' => 'input'],
            ['sequence' => 20, 'item' => $rm002, 'quantity' => 0.5000, 'type' => 'input'],
        ] as $line) {
            FormulaLine::firstOrCreate([
                'formula_id' => $formula->id,
                'item_id' => $line['item']->id,
                'sequence' => $line['sequence'],
            ], [
                'quantity' => $line['quantity'],
                'uom_id' => $kg->id,
                'type' => $line['type'],
            ]);
        }
    }
}

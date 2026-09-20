<?php

namespace Database\Seeders;

use App\Models\CostRollup;
use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CostRollupSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('cost_rollups')) {
            $this->command?->warn('Skipping CostRollupSeeder: table "cost_rollups" not found.');

            return;
        }

        $fg001 = Item::where('code', 'FG001')->first();
        if (! $fg001) {
            $this->command?->warn('Skipping CostRollupSeeder: item FG001 not found.');

            return;
        }

        CostRollup::firstOrCreate([
            'item_id' => $fg001->id,
            'effective_from' => now()->startOfMonth()->toDateString(),
        ], [
            'material_cost' => 7.10,
            'labor_cost' => 5.00,
            'machine_cost' => 3.00,
            'overhead_cost' => 2.00,
            'total_cost' => 17.10,
            'cost_per_unit' => 17.10,
            'status' => 'approved',
        ]);
    }
}

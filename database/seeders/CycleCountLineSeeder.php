<?php

namespace Database\Seeders;

use App\Models\CycleCountLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CycleCountLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('cycle_count_lines')) {
            $this->command?->warn('Skipping CycleCountLineSeeder: table "cycle_count_lines" does not exist in the current schema.');

            return;
        }

        CycleCountLine::create([
            'cycle_count_id' => 1,
            'item_id' => 1,
            'system_quantity' => 100,
            'counted_quantity' => 98,
            'variance' => -2,
        ]);
    }
}

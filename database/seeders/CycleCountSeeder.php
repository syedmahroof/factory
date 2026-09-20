<?php

namespace Database\Seeders;

use App\Models\CycleCount;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CycleCountSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('cycle_counts')) {
            $this->command?->warn('Skipping CycleCountSeeder: table "cycle_counts" does not exist in the current schema.');

            return;
        }

        CycleCount::create([
            'number' => 'CC-0001',
            'warehouse_id' => 1,
            'created_by' => 1,
            'count_date' => now()->toDateString(),
            'type' => 'cycle',
            'status' => 'draft',
        ]);
    }
}

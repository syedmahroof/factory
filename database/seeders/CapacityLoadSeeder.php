<?php

namespace Database\Seeders;

use App\Models\CapacityLoad;
use App\Models\Plant;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CapacityLoadSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('capacity_loads')) {
            $this->command?->warn('Skipping CapacityLoadSeeder: table "capacity_loads" not found.');

            return;
        }

        $wc = WorkCenter::where('code', 'CNC01')->first();
        $plant = Plant::where('code', 'PLT001')->first();
        if (! $wc || ! $plant) {
            $this->command?->warn('Skipping CapacityLoadSeeder: work center CNC01 or plant PLT001 not found.');

            return;
        }

        CapacityLoad::firstOrCreate([
            'work_center_id' => $wc->id,
            'plant_id' => $plant->id,
            'load_date' => now()->toDateString(),
        ], [
            'available_hours' => 16.00,
            'loaded_hours' => 12.80,
            'utilization_percentage' => 80.00,
        ]);
    }
}

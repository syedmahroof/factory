<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ForecastSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('forecasts')) {
            $this->command?->warn('Skipping ForecastSeeder: table "forecasts" not found.');

            return;
        }

        $fg001 = Item::where('code', 'FG001')->first();
        $plant = Plant::where('code', 'PLT001')->first();
        if (! $fg001 || ! $plant) {
            $this->command?->warn('Skipping ForecastSeeder: item FG001 or plant PLT001 not found.');

            return;
        }

        // Forecast uses HasUuids which writes the uuid into the bigint id column,
        // so insert through the query builder.
        DB::table('forecasts')->updateOrInsert(
            ['number' => 'FC-2026-09'],
            [
                'uuid' => (string) Str::uuid(),
                'item_id' => $fg001->id,
                'plant_id' => $plant->id,
                'forecast_date' => now()->startOfMonth()->toDateString(),
                'quantity' => 250,
                'type' => 'forecast',
                'status' => 'active',
                'updated_at' => now(),
            ]
        );
    }
}

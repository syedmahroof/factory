<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlannedOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('planned_orders')) {
            $this->command?->warn('Skipping PlannedOrderSeeder: table "planned_orders" not found.');

            return;
        }

        $rm001 = Item::where('code', 'RM001')->first();
        $plant = Plant::where('code', 'PLT001')->first();
        if (! $rm001 || ! $plant) {
            $this->command?->warn('Skipping PlannedOrderSeeder: item RM001 or plant PLT001 not found.');

            return;
        }

        // PlannedOrder uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('planned_orders')->updateOrInsert(
            ['number' => 'PL-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'item_id' => $rm001->id,
                'plant_id' => $plant->id,
                'source' => 'mrp',
                'planned_quantity' => 800,
                'required_date' => now()->addDays(14)->toDateString(),
                'planned_start_date' => now()->addDays(7)->toDateString(),
                'order_type' => 'purchase',
                'status' => 'planned',
                'updated_at' => now(),
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\ProductionOrder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ReworkOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('rework_orders')) {
            $this->command?->warn('Skipping ReworkOrderSeeder: table "rework_orders" not found.');

            return;
        }

        $mo = ProductionOrder::where('number', 'MO-0001')->first();
        if (! $mo) {
            $this->command?->warn('Skipping ReworkOrderSeeder: production order MO-0001 not found.');

            return;
        }

        // ReworkOrder uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('rework_orders')->updateOrInsert(
            ['number' => 'RW-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'production_order_id' => $mo->id,
                'quantity' => 5,
                'defect_description' => 'Surface scratches detected during final inspection.',
                'status' => 'open',
                'estimated_cost' => 120,
                'actual_cost' => 0,
                'updated_at' => now(),
            ]
        );
    }
}

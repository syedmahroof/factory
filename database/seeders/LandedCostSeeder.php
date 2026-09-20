<?php

namespace Database\Seeders;

use App\Models\GoodsReceipt;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class LandedCostSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('landed_costs')) {
            $this->command?->warn('Skipping LandedCostSeeder: table "landed_costs" not found.');

            return;
        }

        $gr = GoodsReceipt::where('number', 'GR-0001')->first();
        if (! $gr) {
            $this->command?->warn('Skipping LandedCostSeeder: goods receipt GR-0001 not found.');

            return;
        }

        // LandedCost uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('landed_costs')->updateOrInsert(
            ['goods_receipt_id' => $gr->id],
            [
                'uuid' => (string) Str::uuid(),
                'freight' => 150,
                'duty' => 20,
                'insurance' => 10,
                'other_charges' => 0,
                'total_landed_cost' => 180,
                'allocation_method' => 'value',
                'status' => 'posted',
                'updated_at' => now(),
            ]
        );
    }
}

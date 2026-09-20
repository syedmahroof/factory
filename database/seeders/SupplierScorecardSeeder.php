<?php

namespace Database\Seeders;

use App\Models\Supplier;
use App\Models\SupplierScorecard;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SupplierScorecardSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('supplier_scorecards')) {
            $this->command?->warn('Skipping SupplierScorecardSeeder: table "supplier_scorecards" not found.');

            return;
        }

        $supplier = Supplier::where('code', 'SUP001')->first();
        if (! $supplier) {
            $this->command?->warn('Skipping SupplierScorecardSeeder: supplier SUP001 not found.');

            return;
        }

        SupplierScorecard::firstOrCreate([
            'supplier_id' => $supplier->id,
            'period' => '2026-08',
        ], [
            'total_orders' => 12,
            'on_time_orders' => 10,
            'otif_percentage' => 83.33,
            'total_items_received' => 4800,
            'rejected_items' => 35,
            'rejection_rate' => 0.73,
            'avg_price_variance' => 1.20,
            'avg_response_time_days' => 2.5,
            'overall_score' => 88.5,
        ]);
    }
}

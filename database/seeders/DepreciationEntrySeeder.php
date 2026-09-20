<?php

namespace Database\Seeders;

use App\Models\DepreciationEntry;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DepreciationEntrySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('depreciation_entries')) {
            $this->command?->warn('Skipping DepreciationEntrySeeder: table "depreciation_entries" does not exist in the current schema.');

            return;
        }

        DepreciationEntry::create([
            'fixed_asset_id' => 1,
            'depreciation_date' => now()->startOfMonth()->toDateString(),
            'depreciation_amount' => 1125,
            'accumulated_after' => 31125,
            'status' => 'draft',
        ]);
    }
}

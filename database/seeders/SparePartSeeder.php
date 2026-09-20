<?php

namespace Database\Seeders;

use App\Models\SparePart;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SparePartSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('spare_parts')) {
            $this->command?->warn('Skipping SparePartSeeder: table "spare_parts" does not exist in the current schema.');

            return;
        }

        SparePart::create([
            'item_id' => 1,
            'asset_id' => 1,
            'min_stock' => 2,
            'max_stock' => 10,
            'reorder_point' => 3,
        ]);
    }
}

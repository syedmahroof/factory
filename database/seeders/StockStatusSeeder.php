<?php

namespace Database\Seeders;

use App\Models\StockStatus;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StockStatusSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('stock_statuses')) {
            $this->command?->warn('Skipping StockStatusSeeder: table "stock_statuses" not found.');

            return;
        }

        foreach ([
            ['code' => 'AVL', 'name' => 'Available', 'color' => 'green', 'available_for_production' => true, 'available_for_sales' => true, 'available_for_issue' => true],
            ['code' => 'QUR', 'name' => 'Quarantine', 'color' => 'yellow', 'available_for_production' => false, 'available_for_sales' => false, 'available_for_issue' => false],
            ['code' => 'BLK', 'name' => 'Blocked', 'color' => 'red', 'available_for_production' => false, 'available_for_sales' => false, 'available_for_issue' => false],
        ] as $status) {
            StockStatus::firstOrCreate(['code' => $status['code']], $status);
        }
    }
}

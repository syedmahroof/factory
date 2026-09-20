<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\Plant;
use App\Models\StockBalance;
use App\Models\StockStatus;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class StockBalanceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('stock_balances')) {
            $this->command?->warn('Skipping StockBalanceSeeder: table "stock_balances" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $plant = Plant::where('code', 'PLT001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();
        $avail = StockStatus::where('code', 'AVL')->first();
        if (! $company || ! $warehouse || ! $avail) {
            $this->command?->warn('Skipping StockBalanceSeeder: required records not found.');

            return;
        }

        foreach ([
            ['item' => 'RM001', 'quantity' => 500, 'unit_cost' => 2.50],
            ['item' => 'RM002', 'quantity' => 200, 'unit_cost' => 4.20],
            ['item' => 'FG001', 'quantity' => 120, 'unit_cost' => 25.00],
            ['item' => 'FG002', 'quantity' => 60, 'unit_cost' => 38.00],
        ] as $row) {
            $item = Item::where('code', $row['item'])->first();
            if (! $item) {
                continue;
            }

            StockBalance::firstOrCreate([
                'company_id' => $company->id,
                'warehouse_id' => $warehouse->id,
                'item_id' => $item->id,
                'stock_status_id' => $avail->id,
            ], [
                'plant_id' => $plant?->id,
                'quantity' => $row['quantity'],
                'reserved_quantity' => 0,
                'available_quantity' => $row['quantity'],
                'unit_cost' => $row['unit_cost'],
                'total_value' => $row['quantity'] * $row['unit_cost'],
            ]);
        }
    }
}

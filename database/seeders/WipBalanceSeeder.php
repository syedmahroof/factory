<?php

namespace Database\Seeders;

use App\Models\WipBalance;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WipBalanceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('wip_balances')) {
            $this->command?->warn('Skipping WipBalanceSeeder: table "wip_balances" does not exist in the current schema.');

            return;
        }

        WipBalance::create([
            'production_order_id' => 1,
            'item_id' => 1,
            'quantity' => 45,
            'material_cost' => 225,
            'labor_cost' => 90,
            'overhead_cost' => 20,
            'total_cost' => 335,
        ]);
    }
}

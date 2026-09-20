<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\Company;
use App\Models\Item;
use App\Models\Plant;
use App\Models\ProductionOrder;
use App\Models\Routing;
use App\Models\Uom;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ProductionOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('production_orders')) {
            $this->command?->warn('Skipping ProductionOrderSeeder: table "production_orders" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        $ea = Uom::where('code', 'EA')->first();
        $plant = Plant::where('code', 'PLT001')->first();
        $warehouse = Warehouse::where('code', 'WH001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $fg001 || ! $ea || ! $admin) {
            $this->command?->warn('Skipping ProductionOrderSeeder: required records not found.');

            return;
        }

        $bom = Bom::where('item_id', $fg001->id)->first();
        $routing = Routing::where('code', 'RT-FG001')->first();

        ProductionOrder::firstOrCreate(['number' => 'MO-0001'], [
            'company_id' => $company->id,
            'item_id' => $fg001->id,
            'bom_id' => $bom?->id,
            'routing_id' => $routing?->id,
            'plant_id' => $plant?->id,
            'warehouse_id' => $warehouse?->id,
            'type' => 'make_to_order',
            'planned_quantity' => 100,
            'actual_quantity' => 0,
            'scrap_quantity' => 0,
            'uom_id' => $ea->id,
            'priority' => 5,
            'planned_start_date' => now()->toDateString(),
            'planned_end_date' => now()->addDays(7)->toDateString(),
            'status' => 'released',
            'estimated_cost' => 2500,
            'actual_cost' => 0,
            'notes' => 'Customer order MO-0001 for FG001.',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}

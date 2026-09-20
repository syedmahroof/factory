<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('items')) {
            $this->command?->warn('Skipping ItemSeeder: table "items" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping ItemSeeder: company FAC001 not found.');

            return;
        }

        $kg = Uom::where('code', 'KG')->first();
        $ea = Uom::where('code', 'EA')->first();
        $rm = ItemCategory::where('code', 'RM')->first();
        $fg = ItemCategory::where('code', 'FG')->first();

        foreach ([
            ['code' => 'RM001', 'name' => 'Steel Sheet 3mm', 'type' => 'raw_material', 'category' => $rm, 'uom' => $kg, 'standard_cost' => 2.50, 'purchase_price' => 2.50],
            ['code' => 'RM002', 'name' => 'Aluminum Bar', 'type' => 'raw_material', 'category' => $rm, 'uom' => $kg, 'standard_cost' => 4.20, 'purchase_price' => 4.20],
            ['code' => 'FG001', 'name' => 'Steel Bracket Assembly', 'type' => 'finished', 'category' => $fg, 'uom' => $ea, 'standard_cost' => 25.00, 'selling_price' => 45.00],
            ['code' => 'FG002', 'name' => 'Aluminum Housing', 'type' => 'finished', 'category' => $fg, 'uom' => $ea, 'standard_cost' => 38.00, 'selling_price' => 62.00],
        ] as $item) {
            Item::firstOrCreate(['code' => $item['code']], [
                'company_id' => $company->id,
                'name' => $item['name'],
                'description' => $item['name'],
                'type' => $item['type'],
                'category_id' => $item['category']?->id,
                'base_uom_id' => $item['uom']?->id,
                'standard_cost' => $item['standard_cost'],
                'purchase_price' => $item['purchase_price'] ?? 0,
                'selling_price' => $item['selling_price'] ?? 0,
                'tax_rate' => 0,
                'valuation_method' => 'fifo',
                'reorder_level' => 50,
                'reorder_quantity' => 200,
                'minimum_stock' => 25,
                'maximum_stock' => 1000,
                'safety_stock' => 25,
                'lead_time_days' => 7,
                'is_active' => true,
            ]);
        }
    }
}

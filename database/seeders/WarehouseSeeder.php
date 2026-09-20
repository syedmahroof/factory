<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plant;
use App\Models\Warehouse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WarehouseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('warehouses')) {
            $this->command?->warn('Skipping WarehouseSeeder: table "warehouses" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping WarehouseSeeder: company FAC001 not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();

        foreach ([
            ['code' => 'WH001', 'name' => 'Main Warehouse'],
            ['code' => 'WH002', 'name' => 'Raw Materials Store'],
        ] as $wh) {
            Warehouse::firstOrCreate(['code' => $wh['code']], array_merge($wh, [
                'company_id' => $company->id,
                'plant_id' => $plant?->id,
                'address' => '100 Industrial Parkway, Detroit MI',
                'is_quarantine' => false,
                'is_active' => true,
            ]));
        }
    }
}

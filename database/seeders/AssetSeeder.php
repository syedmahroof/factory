<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Company;
use App\Models\Plant;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('assets')) {
            $this->command?->warn('Skipping AssetSeeder: table "assets" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping AssetSeeder: company FAC001 not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();
        $cnc = WorkCenter::where('code', 'CNC01')->first();

        foreach ([
            ['code' => 'AST-0001', 'name' => 'CNC Machine 1', 'type' => 'machine', 'work_center_id' => $cnc?->id, 'criticality' => 'high', 'purchase_cost' => 150000],
            ['code' => 'AST-0002', 'name' => 'Welding Station 1', 'type' => 'machine', 'work_center_id' => null, 'criticality' => 'medium', 'purchase_cost' => 42000],
        ] as $asset) {
            Asset::firstOrCreate(['code' => $asset['code']], [
                'company_id' => $company->id,
                'plant_id' => $plant?->id,
                'name' => $asset['name'],
                'description' => $asset['name'],
                'type' => $asset['type'],
                'work_center_id' => $asset['work_center_id'],
                'manufacturer' => 'Mazak',
                'model' => 'VCN-530C',
                'serial_number' => 'SN-'.$asset['code'],
                'purchase_date' => now()->subYears(2)->toDateString(),
                'installation_date' => now()->subYears(2)->addDays(30)->toDateString(),
                'purchase_cost' => $asset['purchase_cost'],
                'current_value' => $asset['purchase_cost'] * 0.7,
                'warranty_expiry' => now()->addYear()->toDateString(),
                'criticality' => $asset['criticality'],
                'status' => 'active',
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plant;
use App\Models\WorkCenter;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class WorkCenterSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('work_centers')) {
            $this->command?->warn('Skipping WorkCenterSeeder: table "work_centers" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping WorkCenterSeeder: company FAC001 not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();

        foreach ([
            ['code' => 'CNC01', 'name' => 'CNC Machine 1', 'type' => 'machine', 'capacity_per_hour' => 40, 'cost_per_hour' => 85, 'setup_cost' => 25],
            ['code' => 'WLD01', 'name' => 'Welding Station', 'type' => 'machine', 'capacity_per_hour' => 25, 'cost_per_hour' => 60, 'setup_cost' => 15],
            ['code' => 'ASM01', 'name' => 'Assembly Bench 1', 'type' => 'labor', 'capacity_per_hour' => 30, 'cost_per_hour' => 35, 'setup_cost' => 5],
        ] as $wc) {
            WorkCenter::firstOrCreate(['code' => $wc['code']], array_merge($wc, [
                'company_id' => $company->id,
                'plant_id' => $plant?->id,
                'capacity_per_day' => $wc['capacity_per_hour'] * 8,
                'is_bottleneck' => false,
                'is_active' => true,
            ]));
        }
    }
}

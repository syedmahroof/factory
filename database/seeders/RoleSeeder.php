<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('roles')) {
            $this->command?->warn('Skipping RoleSeeder: table "roles" not found.');

            return;
        }

        foreach ([
            'super_admin',
            'company_admin',
            'plant_manager',
            'production_planner',
            'production_supervisor',
            'shop_floor_operator',
            'procurement_manager',
            'warehouse_manager',
            'quality_manager',
            'maintenance_manager',
            'sales_manager',
            'finance_manager',
            'hr_manager',
        ] as $role) {
            Role::firstOrCreate(['name' => $role], [
                'display_name' => ucwords(str_replace('_', ' ', $role)),
            ]);
        }
    }
}

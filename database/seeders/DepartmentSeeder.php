<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DepartmentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('departments')) {
            $this->command?->warn('Skipping DepartmentSeeder: table "departments" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping DepartmentSeeder: company FAC001 not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();

        foreach ([
            ['code' => 'PRO', 'name' => 'Production'],
            ['code' => 'PUR', 'name' => 'Procurement'],
            ['code' => 'WAR', 'name' => 'Warehouse'],
            ['code' => 'QUA', 'name' => 'Quality'],
            ['code' => 'MAI', 'name' => 'Maintenance'],
            ['code' => 'SAL', 'name' => 'Sales'],
            ['code' => 'FIN', 'name' => 'Finance'],
            ['code' => 'HR', 'name' => 'Human Resources'],
            ['code' => 'ENG', 'name' => 'Engineering'],
            ['code' => 'IT', 'name' => 'Information Technology'],
        ] as $dept) {
            Department::firstOrCreate(['code' => $dept['code']], array_merge($dept, [
                'company_id' => $company->id,
                'plant_id' => $plant?->id,
                'is_active' => true,
            ]));
        }
    }
}

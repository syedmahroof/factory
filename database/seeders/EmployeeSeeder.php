<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\Employee;
use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('employees')) {
            $this->command?->warn('Skipping EmployeeSeeder: table "employees" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping EmployeeSeeder: company FAC001 not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();
        $production = Department::where('code', 'PRO')->first();
        $admin = User::where('email', 'admin@factory.com')->first();

        foreach ([
            ['employee_code' => 'EMP-0001', 'first_name' => 'John', 'last_name' => 'Doe', 'email' => 'john.doe@amc.example.com'],
            ['employee_code' => 'EMP-0002', 'first_name' => 'Jane', 'last_name' => 'Smith', 'email' => 'jane.smith@amc.example.com'],
        ] as $employee) {
            $record = Employee::firstOrNew(['employee_code' => $employee['employee_code']]);
            if (! $record->exists) {
                $record->uuid = (string) Str::uuid();
            }
            $record->fill([
                'user_id' => $admin?->id,
                'company_id' => $company->id,
                'plant_id' => $plant?->id,
                'department_id' => $production?->id,
                'first_name' => $employee['first_name'],
                'last_name' => $employee['last_name'],
                'email' => $employee['email'],
                'phone' => '+1 313 555 0100',
                'date_of_birth' => '1985-04-12',
                'gender' => 'male',
                'address' => '50 Employee Lane, Detroit MI',
                'hire_date' => now()->subYears(3)->toDateString(),
                'employment_type' => 'full_time',
                'status' => 'active',
                'emergency_contact_name' => 'Next of Kin',
                'emergency_contact_phone' => '+1 313 555 0199',
            ])->save();
        }
    }
}

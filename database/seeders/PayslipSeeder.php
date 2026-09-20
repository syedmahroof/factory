<?php

namespace Database\Seeders;

use App\Models\Payslip;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PayslipSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('payslips')) {
            $this->command?->warn('Skipping PayslipSeeder: table "payslips" does not exist in the current schema.');

            return;
        }

        Payslip::create([
            'payroll_run_id' => 1,
            'employee_id' => 1,
            'basic_salary' => 3200,
            'overtime_pay' => 120,
            'allowances' => 200,
            'bonus' => 0,
            'gross_pay' => 3520,
            'tax' => 480,
            'social_security' => 215,
            'other_deductions' => 40,
            'net_pay' => 2785,
        ]);
    }
}

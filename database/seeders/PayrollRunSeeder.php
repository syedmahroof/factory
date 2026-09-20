<?php

namespace Database\Seeders;

use App\Models\PayrollRun;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PayrollRunSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('payroll_runs')) {
            $this->command?->warn('Skipping PayrollRunSeeder: table "payroll_runs" does not exist in the current schema.');

            return;
        }

        PayrollRun::create([
            'number' => 'PR-2026-09',
            'period' => '2026-09',
            'prepared_by' => 1,
            'total_gross' => 25000,
            'total_deductions' => 4200,
            'total_net' => 20800,
            'status' => 'draft',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AttendanceSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('attendance')) {
            $this->command?->warn('Skipping AttendanceSeeder: table "attendance" not found.');

            return;
        }

        $employee = Employee::where('employee_code', 'EMP-0001')->first();
        $shift = Shift::where('code', 'MORN')->first();
        if (! $employee || ! $shift) {
            $this->command?->warn('Skipping AttendanceSeeder: employee EMP-0001 or morning shift not found.');

            return;
        }

        // The Attendance model does not declare $table, so Eloquent infers
        // "attendances" while the real table is "attendance" — insert directly.
        DB::table('attendance')->updateOrInsert(
            [
                'employee_id' => $employee->id,
                'date' => now()->toDateString(),
            ],
            [
                'shift_id' => $shift->id,
                'clock_in' => now()->format('Y-m-d').' 06:05:00',
                'clock_out' => now()->format('Y-m-d').' 14:10:00',
                'hours_worked' => 8.00,
                'overtime_hours' => 0,
                'status' => 'present',
                'source' => 'manual',
                'notes' => 'On time.',
                'updated_at' => now(),
            ]
        );
    }
}

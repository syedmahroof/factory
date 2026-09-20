<?php

namespace Database\Seeders;

use App\Models\PreventiveMaintenanceSchedule;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PreventiveMaintenanceScheduleSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('preventive_maintenance_schedules')) {
            $this->command?->warn('Skipping PreventiveMaintenanceScheduleSeeder: table "preventive_maintenance_schedules" does not exist in the current schema.');

            return;
        }

        PreventiveMaintenanceSchedule::create([
            'maintenance_plan_id' => 1,
            'asset_id' => 1,
            'trigger_type' => 'calendar',
            'interval_days' => 90,
            'next_due_date' => now()->addDays(90)->toDateString(),
            'is_active' => true,
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\CalibrationRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CalibrationRecordSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('calibration_records')) {
            $this->command?->warn('Skipping CalibrationRecordSeeder: table "calibration_records" does not exist in the current schema.');

            return;
        }

        CalibrationRecord::create([
            'instrument_code' => 'CAL-0001',
            'instrument_name' => 'Digital Caliper 150mm',
            'plant_id' => 1,
            'last_calibration_date' => now()->subMonths(6)->toDateString(),
            'next_calibration_date' => now()->addMonths(6)->toDateString(),
            'status' => 'calibrated',
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\OeeRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class OeeRecordSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('oee_records')) {
            $this->command?->warn('Skipping OeeRecordSeeder: table "oee_records" does not exist in the current schema.');

            return;
        }

        OeeRecord::create([
            'work_center_id' => 1,
            'record_date' => now()->toDateString(),
            'available_hours' => 16,
            'operating_hours' => 13.5,
            'downtime_hours' => 2.5,
            'total_count' => 480,
            'good_count' => 465,
            'availability' => 84.38,
            'performance' => 88.89,
            'quality' => 96.88,
            'oee' => 72.62,
        ]);
    }
}

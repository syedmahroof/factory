<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Shift;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ShiftSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('shifts')) {
            $this->command?->warn('Skipping ShiftSeeder: table "shifts" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping ShiftSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'MORN', 'name' => 'Morning Shift', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'hours' => 8],
            ['code' => 'AFTN', 'name' => 'Afternoon Shift', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'hours' => 8],
            ['code' => 'NIGHT', 'name' => 'Night Shift', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'hours' => 8],
        ] as $shift) {
            Shift::firstOrCreate(['company_id' => $company->id, 'code' => $shift['code']], array_merge($shift, [
                'is_active' => true,
            ]));
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\ShiftRoster;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ShiftRosterSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('shift_rosters')) {
            $this->command?->warn('Skipping ShiftRosterSeeder: table "shift_rosters" does not exist in the current schema.');

            return;
        }

        ShiftRoster::create([
            'shift_id' => 1,
            'employee_id' => 1,
            'roster_date' => now()->toDateString(),
            'status' => 'assigned',
        ]);
    }
}

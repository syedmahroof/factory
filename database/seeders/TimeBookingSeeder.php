<?php

namespace Database\Seeders;

use App\Models\TimeBooking;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class TimeBookingSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('time_bookings')) {
            $this->command?->warn('Skipping TimeBookingSeeder: table "time_bookings" does not exist in the current schema.');

            return;
        }

        TimeBooking::create([
            'employee_id' => 1,
            'production_order_id' => 1,
            'work_center_id' => 1,
            'booking_date' => now()->toDateString(),
            'start_time' => '06:00:00',
            'end_time' => '10:00:00',
            'hours' => 4,
            'type' => 'direct',
        ]);
    }
}

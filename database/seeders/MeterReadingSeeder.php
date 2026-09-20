<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\MeterReading;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MeterReadingSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('meter_readings') || ! Schema::hasTable('meters')) {
            $this->command?->warn('Skipping MeterReadingSeeder: tables "meter_readings"/"meters" not found.');

            return;
        }

        $asset = Asset::where('code', 'AST-0001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $asset || ! $admin) {
            $this->command?->warn('Skipping MeterReadingSeeder: asset or admin user not found.');

            return;
        }

        $meter = DB::table('meters')->where('asset_id', $asset->id)->where('name', 'Operating Hours')->first();
        if (! $meter) {
            DB::table('meters')->insert([
                'asset_id' => $asset->id,
                'name' => 'Operating Hours',
                'unit' => 'h',
                'current_reading' => 3120,
                'min_threshold' => 0,
                'max_threshold' => 5000,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $meter = DB::table('meters')->where('asset_id', $asset->id)->where('name', 'Operating Hours')->first();
        }

        MeterReading::firstOrCreate([
            'meter_id' => $meter->id,
            'reading_date' => now()->toDateString(),
        ], [
            'reading' => 3120,
            'recorded_by' => $admin->id,
            'source' => 'manual',
            'notes' => 'End of week reading.',
        ]);
    }
}

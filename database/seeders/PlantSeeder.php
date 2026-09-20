<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Plant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PlantSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('plants')) {
            $this->command?->warn('Skipping PlantSeeder: table "plants" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping PlantSeeder: company FAC001 not found.');

            return;
        }

        // PLT001 is created by DatabaseSeeder; the Plant model uses HasUuids which
        // writes the uuid into the bigint id column, so new rows must be inserted
        // through the query builder instead of the model.
        $plants = [
            ['code' => 'PLT001', 'name' => 'Main Factory', 'city' => 'Detroit', 'state' => 'MI', 'timezone' => 'America/New_York'],
            ['code' => 'PLT002', 'name' => 'Assembly Plant', 'city' => 'Toledo', 'state' => 'OH', 'timezone' => 'America/New_York'],
        ];

        foreach ($plants as $plant) {
            if (! Plant::where('code', $plant['code'])->exists()) {
                DB::table('plants')->insert([
                    'uuid' => (string) Str::uuid(),
                    'company_id' => $company->id,
                    'code' => $plant['code'],
                    'name' => $plant['name'],
                    'address' => '200 Assembly Way',
                    'city' => $plant['city'],
                    'state' => $plant['state'],
                    'country' => 'US',
                    'phone' => '+1 419 555 0100',
                    'email' => strtolower($plant['code']).'@amc.example.com',
                    'timezone' => $plant['timezone'],
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}

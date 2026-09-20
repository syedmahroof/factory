<?php

namespace Database\Seeders;

use App\Models\Warehouse;
use App\Models\Zone;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ZoneSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('zones')) {
            $this->command?->warn('Skipping ZoneSeeder: table "zones" not found.');

            return;
        }

        $warehouse = Warehouse::where('code', 'WH001')->first();
        if (! $warehouse) {
            $this->command?->warn('Skipping ZoneSeeder: warehouse WH001 not found.');

            return;
        }

        foreach ([
            ['code' => 'RECV', 'name' => 'Receiving Area'],
            ['code' => 'STG', 'name' => 'General Storage'],
            ['code' => 'QA', 'name' => 'Quality Hold Area'],
        ] as $zone) {
            Zone::firstOrCreate(['warehouse_id' => $warehouse->id, 'code' => $zone['code']], array_merge($zone, [
                'is_active' => true,
            ]));
        }
    }
}

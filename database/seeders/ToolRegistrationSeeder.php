<?php

namespace Database\Seeders;

use App\Models\ToolRegistration;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ToolRegistrationSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('tool_registrations')) {
            $this->command?->warn('Skipping ToolRegistrationSeeder: table "tool_registrations" does not exist in the current schema.');

            return;
        }

        ToolRegistration::create([
            'code' => 'TL-0001',
            'name' => 'Punch Tip 12mm',
            'type' => 'tool',
            'plant_id' => 1,
            'life_limit' => 10000,
            'current_life_used' => 3450,
            'status' => 'active',
        ]);
    }
}

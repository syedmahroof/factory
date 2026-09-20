<?php

namespace Database\Seeders;

use App\Models\QualityGate;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QualityGateSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quality_gates')) {
            $this->command?->warn('Skipping QualityGateSeeder: table "quality_gates" does not exist in the current schema.');

            return;
        }

        QualityGate::create([
            'quality_plan_id' => 1,
            'gate_name' => 'Final visual check',
            'inspection_type' => 'final',
            'blocks_completion' => true,
            'is_active' => true,
        ]);
    }
}

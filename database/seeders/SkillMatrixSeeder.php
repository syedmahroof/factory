<?php

namespace Database\Seeders;

use App\Models\SkillMatrix;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class SkillMatrixSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('skill_matrix')) {
            $this->command?->warn('Skipping SkillMatrixSeeder: table "skill_matrix" does not exist in the current schema.');

            return;
        }

        SkillMatrix::create([
            'employee_id' => 1,
            'work_center_id' => 1,
            'skill_name' => 'CNC Operation',
            'level' => 'expert',
            'is_certified' => true,
        ]);
    }
}

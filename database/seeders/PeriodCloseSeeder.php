<?php

namespace Database\Seeders;

use App\Models\PeriodClose;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PeriodCloseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('period_closes')) {
            $this->command?->warn('Skipping PeriodCloseSeeder: table "period_closes" does not exist in the current schema.');

            return;
        }

        PeriodClose::create([
            'fiscal_period_id' => 1,
            'module' => 'finance',
            'closed_by' => 1,
            'closed_at' => now(),
            'is_locked' => true,
        ]);
    }
}

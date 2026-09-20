<?php

namespace Database\Seeders;

use App\Models\BudgetLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BudgetLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('budget_lines')) {
            $this->command?->warn('Skipping BudgetLineSeeder: table "budget_lines" does not exist in the current schema.');

            return;
        }

        BudgetLine::create([
            'budget_id' => 1,
            'account_id' => 1,
            'period' => '2026-09',
            'planned_amount' => 5000,
            'actual_amount' => 3200,
        ]);
    }
}

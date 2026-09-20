<?php

namespace Database\Seeders;

use App\Models\CapaAction;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class CapaActionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('capa_actions')) {
            $this->command?->warn('Skipping CapaActionSeeder: table "capa_actions" does not exist in the current schema.');

            return;
        }

        CapaAction::create([
            'number' => 'CAPA-0001',
            'type' => 'corrective',
            'root_cause' => 'Worn punch tip beyond tolerance.',
            'action_description' => 'Replace punch tip and add to PM checklist.',
            'owner_id' => 1,
            'due_date' => now()->addDays(14)->toDateString(),
            'status' => 'open',
        ]);
    }
}

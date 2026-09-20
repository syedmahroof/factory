<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FormulaSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('formulas')) {
            $this->command?->warn('Skipping FormulaSeeder: table "formulas" not found.');

            return;
        }

        $fg001 = Item::where('code', 'FG001')->first();
        if (! $fg001) {
            $this->command?->warn('Skipping FormulaSeeder: item FG001 not found.');

            return;
        }

        // Formula uses HasUuids which writes the uuid into the bigint id column,
        // so insert through the query builder.
        DB::table('formulas')->updateOrInsert(
            ['item_id' => $fg001->id, 'version' => 'A'],
            [
                'uuid' => (string) Str::uuid(),
                'name' => 'FG001 Standard Formula',
                'description' => 'Standard batch formula for the steel bracket assembly.',
                'expected_yield' => 0.9500,
                'potency_factor' => 1.0000,
                'is_active' => true,
                'updated_at' => now(),
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\ReturnMerchandiseLine;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ReturnMerchandiseLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('return_merchandise_lines')) {
            $this->command?->warn('Skipping ReturnMerchandiseLineSeeder: table "return_merchandise_lines" does not exist in the current schema.');

            return;
        }

        ReturnMerchandiseLine::create([
            'rma_id' => 1,
            'item_id' => 1,
            'quantity' => 2,
            'uom_id' => 1,
            'disposition' => 'rework',
            'refund_amount' => 90,
        ]);
    }
}

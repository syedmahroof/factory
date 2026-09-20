<?php

namespace Database\Seeders;

use App\Models\ScrapRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ScrapRecordSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('scrap_records')) {
            $this->command?->warn('Skipping ScrapRecordSeeder: table "scrap_records" does not exist in the current schema.');

            return;
        }

        ScrapRecord::create([
            'production_order_id' => 1,
            'item_id' => 1,
            'quantity' => 3,
            'reason_text' => 'Machining tolerance failure.',
            'scrap_cost' => 7.50,
        ]);
    }
}

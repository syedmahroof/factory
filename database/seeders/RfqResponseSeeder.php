<?php

namespace Database\Seeders;

use App\Models\RfqResponse;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RfqResponseSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('rfq_responses')) {
            $this->command?->warn('Skipping RfqResponseSeeder: table "rfq_responses" does not exist in the current schema.');

            return;
        }

        RfqResponse::create([
            'rfq_id' => 1,
            'supplier_id' => 1,
            'unit_price' => 2.45,
            'lead_time_days' => 10,
        ]);
    }
}

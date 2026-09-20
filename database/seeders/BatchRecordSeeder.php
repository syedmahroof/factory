<?php

namespace Database\Seeders;

use App\Models\BatchRecord;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BatchRecordSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('batch_records')) {
            $this->command?->warn('Skipping BatchRecordSeeder: table "batch_records" does not exist in the current schema.');

            return;
        }

        BatchRecord::create([
            'batch_number' => 'BATCH-0001',
            'production_order_id' => 1,
            'item_id' => 1,
            'planned_quantity' => 100,
            'manufacture_date' => now()->toDateString(),
            'status' => 'open',
        ]);
    }
}

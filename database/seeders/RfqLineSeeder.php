<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\RfqLine;
use App\Models\Supplier;
use App\Models\Uom;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RfqLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('rfq_lines') || ! Schema::hasTable('rfqs')) {
            $this->command?->warn('Skipping RfqLineSeeder: tables "rfq_lines"/"rfqs" not found.');

            return;
        }

        $admin = User::where('email', 'admin@factory.com')->first();
        $kg = Uom::where('code', 'KG')->first();
        $rm001 = Item::where('code', 'RM001')->first();
        $rm002 = Item::where('code', 'RM002')->first();
        $supplier = Supplier::where('code', 'SUP001')->first();
        if (! $admin || ! $kg || ! $rm001 || ! $rm002 || ! $supplier) {
            $this->command?->warn('Skipping RfqLineSeeder: required users/items/UOM/supplier not found.');

            return;
        }

        $rfq = DB::table('rfqs')->where('number', 'RFQ-0001')->first();
        if (! $rfq) {
            DB::table('rfqs')->insert([
                'uuid' => (string) Str::uuid(),
                'company_id' => $supplier->company_id,
                'number' => 'RFQ-0001',
                'created_by' => $admin->id,
                'issue_date' => now()->subDays(10)->toDateString(),
                'closing_date' => now()->addDays(4)->toDateString(),
                'terms_and_conditions' => 'Quote validity 30 days.',
                'status' => 'sent',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $rfq = DB::table('rfqs')->where('number', 'RFQ-0001')->first();
        }

        foreach ([
            ['line_number' => 10, 'item' => $rm001, 'quantity' => 500],
            ['line_number' => 20, 'item' => $rm002, 'quantity' => 200],
        ] as $line) {
            RfqLine::firstOrCreate([
                'rfq_id' => $rfq->id,
                'line_number' => $line['line_number'],
            ], [
                'item_id' => $line['item']->id,
                'quantity' => $line['quantity'],
                'uom_id' => $kg->id,
                'specifications' => 'Standard commercial grade.',
            ]);
        }
    }
}

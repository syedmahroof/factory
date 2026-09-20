<?php

namespace Database\Seeders;

use App\Models\Item;
use App\Models\Quotation;
use App\Models\QuotationLine;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QuotationLineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quotation_lines')) {
            $this->command?->warn('Skipping QuotationLineSeeder: table "quotation_lines" not found.');

            return;
        }

        $quotation = Quotation::where('number', 'Q-0001')->first();
        $ea = Uom::where('code', 'EA')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        if (! $quotation || ! $ea || ! $fg001) {
            $this->command?->warn('Skipping QuotationLineSeeder: required records not found.');

            return;
        }

        QuotationLine::firstOrCreate([
            'quotation_id' => $quotation->id,
            'line_number' => 10,
        ], [
            'item_id' => $fg001->id,
            'quantity' => 100,
            'uom_id' => $ea->id,
            'unit_price' => 45.00,
            'discount_rate' => 0,
            'tax_rate' => 0,
            'line_total' => 4500,
            'notes' => 'Standard bracket assembly, 100 pcs.',
        ]);
    }
}

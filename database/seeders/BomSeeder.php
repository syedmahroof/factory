<?php

namespace Database\Seeders;

use App\Models\Bom;
use App\Models\Company;
use App\Models\Item;
use App\Models\Routing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class BomSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('boms')) {
            $this->command?->warn('Skipping BomSeeder: table "boms" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        $fg002 = Item::where('code', 'FG002')->first();
        if (! $company || ! $fg001 || ! $fg002) {
            $this->command?->warn('Skipping BomSeeder: required items/company not found.');

            return;
        }

        $routingFg1 = Routing::where('code', 'RT-FG001')->first();
        $routingFg2 = Routing::where('code', 'RT-FG002')->first();

        foreach ([
            ['item' => $fg001, 'routing' => $routingFg1],
            ['item' => $fg002, 'routing' => $routingFg2],
        ] as $bom) {
            Bom::firstOrCreate([
                'company_id' => $company->id,
                'item_id' => $bom['item']->id,
                'revision' => 1,
            ], [
                'routing_id' => $bom['routing']?->id,
                'type' => 'normal',
                'scrap_factor' => 2.00,
                'effective_from' => now()->subMonths(6)->toDateString(),
                'status' => 'approved',
                'is_active' => true,
            ]);
        }
    }
}

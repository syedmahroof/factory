<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Routing;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class RoutingSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('routings')) {
            $this->command?->warn('Skipping RoutingSeeder: table "routings" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping RoutingSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'RT-FG001', 'name' => 'FG001 Fabrication Routing', 'revision' => 1],
            ['code' => 'RT-FG002', 'name' => 'FG002 Housing Routing', 'revision' => 1],
        ] as $routing) {
            Routing::firstOrCreate(['code' => $routing['code']], array_merge($routing, [
                'company_id' => $company->id,
                'description' => $routing['name'],
                'effective_from' => now()->subMonths(6)->toDateString(),
                'status' => 'approved',
                'is_active' => true,
            ]));
        }
    }
}

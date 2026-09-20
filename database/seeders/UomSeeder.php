<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Uom;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class UomSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('uoms')) {
            $this->command?->warn('Skipping UomSeeder: table "uoms" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping UomSeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'EA', 'name' => 'Each'],
            ['code' => 'KG', 'name' => 'Kilogram'],
            ['code' => 'L', 'name' => 'Liter'],
            ['code' => 'M', 'name' => 'Meter'],
            ['code' => 'BOX', 'name' => 'Box'],
        ] as $uom) {
            Uom::firstOrCreate(['code' => $uom['code']], array_merge($uom, [
                'company_id' => $company->id,
                'type' => 'primary',
                'base_conversion' => 1,
                'is_active' => true,
            ]));
        }
    }
}

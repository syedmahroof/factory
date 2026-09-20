<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\ItemCategory;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class ItemCategorySeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('item_categories')) {
            $this->command?->warn('Skipping ItemCategorySeeder: table "item_categories" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        if (! $company) {
            $this->command?->warn('Skipping ItemCategorySeeder: company FAC001 not found.');

            return;
        }

        foreach ([
            ['code' => 'RM', 'name' => 'Raw Materials'],
            ['code' => 'FG', 'name' => 'Finished Goods'],
            ['code' => 'SF', 'name' => 'Semi-Finished'],
            ['code' => 'PK', 'name' => 'Packaging'],
            ['code' => 'SP', 'name' => 'Spare Parts'],
        ] as $category) {
            ItemCategory::firstOrCreate(['code' => $category['code']], array_merge($category, [
                'company_id' => $company->id,
                'is_active' => true,
            ]));
        }
    }
}

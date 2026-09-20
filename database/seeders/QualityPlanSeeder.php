<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Item;
use App\Models\QualityPlan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QualityPlanSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quality_plans')) {
            $this->command?->warn('Skipping QualityPlanSeeder: table "quality_plans" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        if (! $company || ! $fg001) {
            $this->command?->warn('Skipping QualityPlanSeeder: company or item FG001 not found.');

            return;
        }

        QualityPlan::firstOrCreate(['code' => 'QP-FG001'], [
            'company_id' => $company->id,
            'name' => 'FG001 Final Inspection Plan',
            'description' => 'Final inspection plan for the steel bracket assembly.',
            'type' => 'final',
            'item_id' => $fg001->id,
            'frequency_days' => 1,
            'sample_percentage' => 5.00,
            'min_sample_size' => 10,
            'is_active' => true,
        ]);
    }
}

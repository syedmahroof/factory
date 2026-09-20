<?php

namespace Database\Seeders;

use App\Models\Inspection;
use App\Models\InspectionResult;
use App\Models\QualityPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class InspectionResultSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('inspection_results') || ! Schema::hasTable('quality_characteristics')) {
            $this->command?->warn('Skipping InspectionResultSeeder: tables "inspection_results"/"quality_characteristics" not found.');

            return;
        }

        $inspection = Inspection::where('number', 'INS-0001')->first();
        $plan = QualityPlan::where('code', 'QP-FG001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $inspection || ! $plan || ! $admin) {
            $this->command?->warn('Skipping InspectionResultSeeder: required records not found.');

            return;
        }

        $characteristic = DB::table('quality_characteristics')->where('quality_plan_id', $plan->id)
            ->where('code', 'DIM-A')->first();
        if (! $characteristic) {
            DB::table('quality_characteristics')->insert([
                'quality_plan_id' => $plan->id,
                'code' => 'DIM-A',
                'name' => 'Outer dimension A',
                'method' => 'caliper',
                'instrument' => 'Digital caliper',
                'data_type' => 'numeric',
                'target_value' => 50,
                'lower_limit' => 49.5,
                'upper_limit' => 50.5,
                'severity' => 'major',
                'is_mandatory' => true,
                'is_active' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $characteristic = DB::table('quality_characteristics')->where('quality_plan_id', $plan->id)
                ->where('code', 'DIM-A')->first();
        }

        InspectionResult::firstOrCreate([
            'inspection_id' => $inspection->id,
            'quality_characteristic_id' => $characteristic->id,
        ], [
            'actual_value' => '49.9',
            'numeric_value' => 49.9,
            'is_within_spec' => true,
            'notes' => 'Within tolerance.',
            'recorded_by' => $admin->id,
            'recorded_at' => now(),
        ]);
    }
}

<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Inspection;
use App\Models\Item;
use App\Models\ProductionOrder;
use App\Models\QualityPlan;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class InspectionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('inspections')) {
            $this->command?->warn('Skipping InspectionSeeder: table "inspections" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $plan = QualityPlan::where('code', 'QP-FG001')->first();
        $fg001 = Item::where('code', 'FG001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $plan || ! $fg001 || ! $admin) {
            $this->command?->warn('Skipping InspectionSeeder: required records not found.');

            return;
        }

        $mo = ProductionOrder::where('number', 'MO-0001')->first();

        $inspection = Inspection::firstOrNew(['number' => 'INS-0001']);
        if (! $inspection->exists) {
            $inspection->uuid = (string) Str::uuid();
        }
        $inspection->fill([
            'company_id' => $company->id,
            'quality_plan_id' => $plan->id,
            'source_type' => 'production_order',
            'source_id' => $mo?->id ?? $fg001->id,
            'item_id' => $fg001->id,
            'quantity_inspected' => 10,
            'quantity_accepted' => 10,
            'quantity_rejected' => 0,
            'quantity_on_hold' => 0,
            'inspector_id' => $admin->id,
            'inspection_date' => now()->toDateString(),
            'result' => 'accepted',
            'notes' => 'All characteristics within specification.',
            'status' => 'completed',
        ])->save();
    }
}

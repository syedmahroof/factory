<?php

namespace Database\Seeders;

use App\Models\Asset;
use App\Models\Company;
use App\Models\MaintenanceOrder;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class MaintenanceOrderSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('maintenance_orders')) {
            $this->command?->warn('Skipping MaintenanceOrderSeeder: table "maintenance_orders" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $asset = Asset::where('code', 'AST-0001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $asset || ! $admin) {
            $this->command?->warn('Skipping MaintenanceOrderSeeder: required records not found.');

            return;
        }

        $order = MaintenanceOrder::firstOrNew(['number' => 'MWO-0001']);
        if (! $order->exists) {
            $order->uuid = (string) Str::uuid();
        }
        $order->fill([
            'company_id' => $company->id,
            'asset_id' => $asset->id,
            'type' => 'preventive',
            'priority' => 'normal',
            'description' => 'Quarterly lubrication and spindle alignment check.',
            'assigned_to' => $admin->id,
            'requested_by' => $admin->id,
            'requested_date' => now()->toDateString(),
            'scheduled_date' => now()->addDays(7)->toDateString(),
            'estimated_hours' => 6,
            'actual_hours' => 0,
            'estimated_cost' => 450,
            'actual_cost' => 0,
            'lockout_tagout' => true,
            'status' => 'planned',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ])->save();
    }
}

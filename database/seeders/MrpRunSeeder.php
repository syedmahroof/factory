<?php

namespace Database\Seeders;

use App\Models\Plant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class MrpRunSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('mrp_runs')) {
            $this->command?->warn('Skipping MrpRunSeeder: table "mrp_runs" not found.');

            return;
        }

        $plant = Plant::where('code', 'PLT001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $plant || ! $admin) {
            $this->command?->warn('Skipping MrpRunSeeder: plant PLT001 or admin user not found.');

            return;
        }

        // Keyed on plant existence (mrp_runs has no natural unique key) so the
        // seeder stays idempotent across re-runs.
        if (! DB::table('mrp_runs')->where('plant_id', $plant->id)->exists()) {
            DB::table('mrp_runs')->insert([
                'plant_id' => $plant->id,
                'run_by' => $admin->id,
                'run_at' => now(),
                'parameters' => json_encode(['horizon_days' => 30, 'include_forecast' => true]),
                'items_processed' => 42,
                'planned_orders_generated' => 6,
                'exceptions_count' => 1,
                'status' => 'completed',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}

<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class PermitToWorkSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('permits_to_work')) {
            $this->command?->warn('Skipping PermitToWorkSeeder: table "permits_to_work" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $admin) {
            $this->command?->warn('Skipping PermitToWorkSeeder: company or admin user not found.');

            return;
        }

        // PermitToWork uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('permits_to_work')->updateOrInsert(
            ['number' => 'PTW-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'type' => 'hot_work',
                'work_description' => 'Welding repair on frame of AST-0002.',
                'issued_by' => $admin->id,
                'worker_id' => $admin->id,
                'valid_from' => now()->format('Y-m-d H:i:s'),
                'valid_to' => now()->addDay()->format('Y-m-d H:i:s'),
                'status' => 'active',
                'safety_measures' => 'Fire watch present, extinguisher on site.',
                'updated_at' => now(),
            ]
        );
    }
}

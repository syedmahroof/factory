<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class EngineeringChangeSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('engineering_changes')) {
            $this->command?->warn('Skipping EngineeringChangeSeeder: table "engineering_changes" not found.');

            return;
        }

        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $admin) {
            $this->command?->warn('Skipping EngineeringChangeSeeder: admin user not found.');

            return;
        }

        // EngineeringChange uses HasUuids which writes the uuid into the bigint
        // id column, so insert through the query builder.
        DB::table('engineering_changes')->updateOrInsert(
            ['number' => 'ECR-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'type' => 'ecr',
                'title' => 'Reduce scrap on bracket assembly',
                'reason' => 'Current die setup produces excessive burrs requiring rework.',
                'impact' => 'Estimated 1.5% scrap reduction on FG001.',
                'requested_by' => $admin->id,
                'effective_date' => now()->addDays(30)->toDateString(),
                'status' => 'approved',
                'updated_at' => now(),
            ]
        );
    }
}

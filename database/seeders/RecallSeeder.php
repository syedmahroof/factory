<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Complaint;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RecallSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('recalls')) {
            $this->command?->warn('Skipping RecallSeeder: table "recalls" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $complaint = Complaint::where('number', 'CMP-0001')->first();
        if (! $company || ! $complaint) {
            $this->command?->warn('Skipping RecallSeeder: company or complaint not found.');

            return;
        }

        // Recall uses HasUuids which writes the uuid into the bigint id column,
        // so insert through the query builder.
        DB::table('recalls')->updateOrInsert(
            ['number' => 'REC-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'complaint_id' => $complaint->id,
                'reason' => 'Suspected thread defect on FG001 units from July production.',
                'affected_lots' => 'All FG001 lots manufactured July 2026',
                'affected_customers' => 'Industrial Solutions Inc.',
                'remaining_stock' => '120 units in WH001',
                'communication_log' => 'Customer notified on receipt of complaint.',
                'status' => 'in_progress',
                'updated_at' => now(),
            ]
        );
    }
}

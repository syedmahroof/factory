<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Ncr;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class NcrSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('ncrs')) {
            $this->command?->warn('Skipping NcrSeeder: table "ncrs" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $admin) {
            $this->command?->warn('Skipping NcrSeeder: company or admin user not found.');

            return;
        }

        $ncr = Ncr::firstOrNew(['number' => 'NCR-0001']);
        if (! $ncr->exists) {
            $ncr->uuid = (string) Str::uuid();
        }
        $ncr->fill([
            'company_id' => $company->id,
            'severity' => 'minor',
            'source' => 'final',
            'defect_description' => 'Minor surface scratches on flange area.',
            'affected_quantity' => 5,
            'reported_by' => $admin->id,
            'reported_date' => now()->subDays(2)->toDateString(),
            'containment_actions' => 'Quarantined affected units pending disposition.',
            'disposition' => 'rework',
            'status' => 'open',
            'assigned_to' => $admin->id,
            'target_close_date' => now()->addDays(7)->toDateString(),
        ])->save();
    }
}

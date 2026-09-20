<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\SafetyIncident;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SafetyIncidentSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('safety_incidents')) {
            $this->command?->warn('Skipping SafetyIncidentSeeder: table "safety_incidents" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $admin) {
            $this->command?->warn('Skipping SafetyIncidentSeeder: company or admin user not found.');

            return;
        }

        $incident = SafetyIncident::firstOrNew(['number' => 'SI-0001']);
        if (! $incident->exists) {
            $incident->uuid = (string) Str::uuid();
        }
        $incident->fill([
            'company_id' => $company->id,
            'type' => 'near_miss',
            'severity' => 'low',
            'description' => 'Operator noticed loose guard on CNC spindle, reported before start.',
            'location' => 'Machine Shop - Bay 2',
            'reported_by' => $admin->id,
            'incident_date' => now()->subDays(1)->toDateString(),
            'immediate_actions' => 'Guard re-secured, machine released for operation.',
            'status' => 'investigating',
        ])->save();
    }
}

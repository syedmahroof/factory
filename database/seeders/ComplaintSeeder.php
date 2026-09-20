<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ComplaintSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('complaints')) {
            $this->command?->warn('Skipping ComplaintSeeder: table "complaints" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $customer = Customer::where('code', 'CUST001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $customer || ! $admin) {
            $this->command?->warn('Skipping ComplaintSeeder: required records not found.');

            return;
        }

        // Complaint uses HasUuids which writes the uuid into the bigint id
        // column, so insert through the query builder.
        DB::table('complaints')->updateOrInsert(
            ['number' => 'CMP-0001'],
            [
                'uuid' => (string) Str::uuid(),
                'company_id' => $company->id,
                'customer_id' => $customer->id,
                'description' => 'Customer reports two brackets with stripped threads.',
                'severity' => 'major',
                'assigned_to' => $admin->id,
                'received_date' => now()->subDays(3)->toDateString(),
                'status' => 'investigating',
                'recall_required' => false,
                'updated_at' => now(),
            ]
        );
    }
}

<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Department;
use App\Models\PurchaseRequisition;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class PurchaseRequisitionSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('purchase_requisitions')) {
            $this->command?->warn('Skipping PurchaseRequisitionSeeder: table "purchase_requisitions" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $admin) {
            $this->command?->warn('Skipping PurchaseRequisitionSeeder: company or admin user not found.');

            return;
        }

        $production = Department::where('code', 'PRO')->first();

        PurchaseRequisition::firstOrCreate(['number' => 'PR-0001'], [
            'company_id' => $company->id,
            'source' => 'manual',
            'requested_by' => $admin->id,
            'department_id' => $production?->id,
            'required_date' => now()->addDays(14)->toDateString(),
            'justification' => 'Quarterly raw material replenishment for FG001 production.',
            'status' => 'approved',
            'approved_by' => $admin->id,
            'approved_at' => now(),
        ]);
    }
}

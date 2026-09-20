<?php

namespace Database\Seeders;

use App\Models\Company;
use App\Models\Journal;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class JournalSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('journals')) {
            $this->command?->warn('Skipping JournalSeeder: table "journals" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $admin = User::where('email', 'admin@factory.com')->first();
        if (! $company || ! $admin) {
            $this->command?->warn('Skipping JournalSeeder: company or admin user not found.');

            return;
        }

        $period = DB::table('fiscal_periods')->where('company_id', $company->id)
            ->where('name', 'FY 2026-09')->first();
        if (! $period) {
            DB::table('fiscal_periods')->insert([
                'company_id' => $company->id,
                'name' => 'FY 2026-09',
                'start_date' => now()->startOfMonth()->toDateString(),
                'end_date' => now()->endOfMonth()->toDateString(),
                'status' => 'open',
                'is_adjustment' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $period = DB::table('fiscal_periods')->where('company_id', $company->id)
                ->where('name', 'FY 2026-09')->first();
        }

        $journal = Journal::firstOrNew(['number' => 'J-0001']);
        if (! $journal->exists) {
            $journal->uuid = (string) Str::uuid();
        }
        $journal->fill([
            'company_id' => $company->id,
            'fiscal_period_id' => $period->id,
            'date' => now()->toDateString(),
            'type' => 'general',
            'description' => 'Sample opening journal entry.',
            'total_debit' => 100,
            'total_credit' => 100,
            'status' => 'posted',
            'created_by' => $admin->id,
            'approved_by' => $admin->id,
            'approved_at' => now(),
            'posted_by' => $admin->id,
            'posted_at' => now(),
        ])->save();
    }
}

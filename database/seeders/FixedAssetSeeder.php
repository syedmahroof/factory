<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Company;
use App\Models\FixedAsset;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FixedAssetSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('fixed_assets')) {
            $this->command?->warn('Skipping FixedAssetSeeder: table "fixed_assets" not found.');

            return;
        }

        $company = Company::where('code', 'FAC001')->first();
        $assetAccount = Account::where('code', '1010')->first();
        $depreciationAccount = Account::where('code', '1200')->first();
        if (! $company || ! $assetAccount || ! $depreciationAccount) {
            $this->command?->warn('Skipping FixedAssetSeeder: company or accounts not found.');

            return;
        }

        $fixedAsset = FixedAsset::firstOrNew(['number' => 'FA-0001']);
        if (! $fixedAsset->exists) {
            $fixedAsset->uuid = (string) Str::uuid();
        }
        $fixedAsset->fill([
            'company_id' => $company->id,
            'name' => 'CNC Machine 1',
            'description' => 'CNC machining center.',
            'account_id' => $assetAccount->id,
            'depreciation_account_id' => $depreciationAccount->id,
            'acquisition_date' => now()->subYears(2)->toDateString(),
            'acquisition_cost' => 150000,
            'accumulated_depreciation' => 30000,
            'net_book_value' => 120000,
            'salvage_value' => 15000,
            'useful_life_months' => 120,
            'depreciation_method' => 'straight_line',
            'status' => 'active',
            'notes' => 'Main production asset.',
        ])->save();
    }
}

<?php

namespace App\Actions\Finance;

use App\Models\DepreciationEntry;
use App\Models\FixedAsset;

class CalculateDepreciation
{
    public function executeForMonth(int $month, int $year): array
    {
        $results = [];
        $assets = FixedAsset::where('status', 'active')->get();

        foreach ($assets as $asset) {
            $depreciableAmount = $asset->acquisition_cost - $asset->salvage_value;
            $monthlyDepreciation = match ($asset->depreciation_method) {
                'straight_line' => $depreciableAmount / $asset->useful_life_months,
                'declining_balance' => ($depreciableAmount - $asset->accumulated_depreciation) * (2 / $asset->useful_life_months),
                default => $depreciableAmount / $asset->useful_life_months,
            };

            if ($asset->accumulated_depreciation + $monthlyDepreciation > $depreciableAmount) {
                $monthlyDepreciation = $depreciableAmount - $asset->accumulated_depreciation;
            }

            if ($monthlyDepreciation <= 0) {
                $asset->update(['status' => 'fully_depreciated']);

                continue;
            }

            $entry = DepreciationEntry::create([
                'fixed_asset_id' => $asset->id,
                'depreciation_date' => "{$year}-{$month}-01",
                'depreciation_amount' => $monthlyDepreciation,
                'accumulated_after' => $asset->accumulated_depreciation + $monthlyDepreciation,
                'status' => 'posted',
            ]);

            $asset->update([
                'accumulated_depreciation' => $asset->accumulated_depreciation + $monthlyDepreciation,
                'book_value' => $asset->book_value - $monthlyDepreciation,
            ]);

            $results[] = $entry;
        }

        return $results;
    }
}

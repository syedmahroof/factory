<?php

namespace App\Http\Resources\Lender;

use App\Models\Lender;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Str;

/**
 * One row of the Lenders register. The fee columns come off the sub-selects
 * ListLendersAction adds, so a lender with no profile row still renders.
 */
class LenderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $feeType = $this->syndication_fee_type === null ? null : (int) $this->syndication_fee_type;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'name_short' => Str::limit((string) $this->name, 27),
            'initials' => $this->initials(),
            'email' => $this->email,
            'username' => $this->username,
            'cell_phone' => $this->cell_phone,
            'company_id' => $this->company_id,
            'company' => $this->Company->name ?? '',
            'management_fee_percentage' => (float) $this->management_fee_percentage,
            'syndication_fee_type' => $feeType,
            'syndication_fee_type_name' => Lender::syndicationFeeTypeOptions()[$feeType] ?? '—',
            'syndication_fee_percentage' => (float) $this->syndication_fee_percentage,
            'lag_time_days' => (int) $this->lag_time_days,
            'status_id' => $this->status_id,
            'status' => $this->status ?: 'Unknown',
            'is_active' => (int) $this->status_id === User::Active,
        ];
    }

    private function initials(): string
    {
        return collect(explode(' ', trim((string) $this->name)))
            ->filter()
            ->take(2)
            ->map(fn ($word) => mb_strtoupper(mb_substr($word, 0, 1)))
            ->implode('');
    }
}

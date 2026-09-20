<?php

namespace App\Http\Resources\Lender;

use App\Models\Lender;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The form payload: the `users` row flattened together with its `lenders`
 * profile. A lender created before the profile table existed has no profile
 * row, so every fee here falls back to its default rather than blowing up.
 */
class LenderProfileResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $profile = $this->Lender;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cell_phone' => $this->cell_phone,
            'company_id' => $this->company_id,
            'company' => $this->Company->name ?? '',
            'status_id' => (int) $this->status_id,
            'status' => $this->status ?: 'Unknown',
            'is_active' => (int) $this->status_id === User::Active,
            'username' => $profile->username ?? null,
            'notification_email' => $profile->notification_email ?? null,
            'management_fee_percentage' => (float) ($profile->management_fee_percentage ?? 0),
            'up_sell_management_fee_percentage' => (float) ($profile->up_sell_management_fee_percentage ?? 0),
            'underwriting_fee_percentage' => (float) ($profile->underwriting_fee_percentage ?? 0),
            'syndication_fee_type' => (int) ($profile->syndication_fee_type ?? Lender::SyndicationFeeNone),
            'syndication_fee_type_name' => $profile->syndication_fee_type_name ?? 'None',
            'syndication_fee_percentage' => (float) ($profile->syndication_fee_percentage ?? 0),
            'up_sell_syndication_fee_percentage' => (float) ($profile->up_sell_syndication_fee_percentage ?? 0),
            'lag_time_days' => (int) ($profile->lag_time_days ?? 0),
            'ip_filtering' => (bool) ($profile->ip_filtering ?? false),
        ];
    }
}

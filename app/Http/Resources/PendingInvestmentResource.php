<?php

namespace App\Http\Resources;

use App\Models\MerchantInvestor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * A participation waiting on approval.
 *
 * @mixin MerchantInvestor
 */
class PendingInvestmentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant_row_id' => $this->merchant_row_id,
            'merchant' => $this->merchant_name,
            'investor_id' => $this->investor_id,
            'investor' => $this->investor_name,

            'funded' => (float) $this->funded,
            'invested' => (float) $this->invested,
            'investment_fee_amount' => (float) $this->investment_fee_amount,
            'rtr' => (float) $this->rtr,
            'share' => (float) $this->share,

            'active_status' => $this->active_status,
            'source' => $this->source,
            'source_label' => $this->source_label,
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_on' => optional($this->created_at)->format('Y-m-d'),
        ];
    }
}

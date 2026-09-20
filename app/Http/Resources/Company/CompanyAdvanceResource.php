<?php

namespace App\Http\Resources\Company;

use App\Models\MerchantInvestor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One row of the company portfolio's Merchants List.
 *
 * @mixin MerchantInvestor
 */
class CompanyAdvanceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'merchant_id' => $this->merchant_id,
            'merchant_row_id' => $this->merchant_row_id,
            'merchant' => strlen((string) $this->merchant_name) > 27
                ? substr((string) $this->merchant_name, 0, 27).'...'
                : $this->merchant_name,
            'investor_id' => $this->investor_id,
            'investor' => $this->investor_name,
            'funded' => (float) $this->funded,
            'rtr' => (float) $this->rtr,
            'invested' => (float) $this->invested,
            'investment_fee_amount' => (float) $this->investment_fee_amount,
            'paid_amount' => (float) $this->paid_amount,
            'percentage' => (float) $this->percentage,
            'balance' => (float) $this->balance,
            'share' => (float) $this->share,
            'paid_management_fee' => (float) $this->paid_management_fee,
            'management_fee_percentage' => (float) $this->management_fee_percentage,
            'paid_agent_fee' => (float) $this->paid_agent_fee,
            'principal' => (float) $this->principal,
            'profit' => (float) $this->profit,
            'completed_percentage' => (float) $this->completed_percentage,
            'source' => MerchantInvestor::sourceLabel($this->source),
            'active_status' => $this->active_status,
        ];
    }
}

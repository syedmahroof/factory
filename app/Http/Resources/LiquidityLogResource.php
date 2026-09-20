<?php

namespace App\Http\Resources;

use App\Models\LiquidityLog;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * One line of the liquidity audit trail.
 *
 * `net_liquidity` is the running balance as it stood after this line, and
 * `batch_no` ties every line written by the same job run together.
 *
 * @mixin LiquidityLog
 */
class LiquidityLogResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $amount = (float) $this->amount;

        return [
            'id' => $this->id,
            'batch_no' => $this->batch_no,
            'description' => $this->description,

            'merchant_id' => $this->merchant_id,
            // merchants.id, which admin.merchants.show binds. merchant_id above is
            // the merchant's users.id — a different number, so the name links with
            // this one and drops the link when the advance is gone.
            'merchant_row_id' => $this->merchant_row_id ? (int) $this->merchant_row_id : null,
            'merchant' => $this->Merchant->name ?? '',

            'amount' => $amount,
            'amount_abs' => abs($amount),
            'amount_exact' => number_format($amount, 8, '.', ''),
            'is_credit' => $amount > 0,

            'net_liquidity' => (float) $this->net_liquidity,
            'net_liquidity_exact' => number_format((float) $this->net_liquidity, 8, '.', ''),

            'creator' => $this->Creator->name ?? '',
            'created_at' => optional($this->created_at)->toIso8601String(),
            'created_on' => optional($this->created_at)->format('Y-m-d'),
            // The register states the day and the time of day separately. Both are
            // formatted here rather than in the browser: the client's timezone would
            // move a line onto a different hour than the one the job wrote it at.
            'created_date' => systemDate($this->created_at),
            'created_time' => optional($this->created_at)->format('g:i A'),
        ];
    }
}

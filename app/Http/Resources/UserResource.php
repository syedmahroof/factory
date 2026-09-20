<?php

namespace App\Http\Resources;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $type = UserType::tryFrom((int) $this->user_type_id);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'cell_phone' => $this->cell_phone,
            'user_type_id' => $this->user_type_id,
            'user_type' => $type?->label(),
            'portal' => $type?->portal()?->value,
            'company_id' => $this->company_id,
            'status_id' => $this->status_id,

            // Money never crosses the wire pre-formatted: the client formats,
            // the API sends the number. Casting keeps 0.00 from arriving as "0.00".
            'liquidity' => $this->when(
                ! is_null($this->liquidity),
                fn () => (float) $this->liquidity
            ),
        ];
    }
}

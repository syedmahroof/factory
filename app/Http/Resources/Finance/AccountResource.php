<?php
namespace App\Http\Resources\Finance;
use Illuminate\Http\Resources\Json\JsonResource;
class AccountResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'type' => $this->type, 'sub_type' => $this->sub_type,
            'account_group_id' => $this->account_group_id,
            'parent_id' => $this->parent_id,
            'opening_balance' => $this->opening_balance,
            'current_balance' => $this->current_balance,
            'is_bank_account' => $this->is_bank_account,
            'is_cash_account' => $this->is_cash_account,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
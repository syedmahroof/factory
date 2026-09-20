<?php
namespace App\Http\Resources\Finance;
use Illuminate\Http\Resources\Json\JsonResource;
class JournalLineResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'account_id' => $this->account_id,
            'debit' => $this->debit, 'credit' => $this->credit,
            'description' => $this->description,
            'cost_center_id' => $this->cost_center_id,
            'account' => $this->whenLoaded('account', fn() => ['id' => $this->account->id, 'code' => $this->account->code, 'name' => $this->account->name]),
        ];
    }
}
<?php
namespace App\Http\Resources\Finance;
use Illuminate\Http\Resources\Json\JsonResource;
class JournalResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'fiscal_period_id' => $this->fiscal_period_id,
            'date' => $this->date, 'type' => $this->type,
            'description' => $this->description,
            'total_debit' => $this->total_debit,
            'total_credit' => $this->total_credit,
            'status' => $this->status, 'posted_at' => $this->posted_at,
            'lines' => JournalLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
        ];
    }
}
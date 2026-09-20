<?php
namespace App\Http\Resources\Quality;
use Illuminate\Http\Resources\Json\JsonResource;
class NcrResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'item_id' => $this->item_id, 'reported_by' => $this->reported_by,
            'severity' => $this->severity, 'status' => $this->status,
            'description' => $this->description, 'root_cause' => $this->root_cause,
            'target_date' => $this->target_date,
            'created_at' => $this->created_at,
        ];
    }
}
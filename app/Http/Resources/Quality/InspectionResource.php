<?php
namespace App\Http\Resources\Quality;
use Illuminate\Http\Resources\Json\JsonResource;
class InspectionResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'item_id' => $this->item_id,
            'inspector_id' => $this->inspector_id, 'inspection_date' => $this->inspection_date,
            'batch_number' => $this->batch_number,
            'quantity_inspected' => $this->quantity_inspected,
            'quantity_accepted' => $this->quantity_accepted,
            'quantity_rejected' => $this->quantity_rejected,
            'status' => $this->status, 'pass_rate' => $this->pass_rate,
            'results' => $this->whenLoaded('results'),
            'created_at' => $this->created_at,
        ];
    }
}
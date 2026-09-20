<?php
namespace App\Http\Resources\Production;
use Illuminate\Http\Resources\Json\JsonResource;
class ProductionOrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'item_id' => $this->item_id, 'type' => $this->type,
            'planned_quantity' => $this->planned_quantity,
            'produced_quantity' => $this->produced_quantity,
            'planned_start_date' => $this->planned_start_date,
            'planned_end_date' => $this->planned_end_date,
            'actual_start_date' => $this->actual_start_date,
            'actual_end_date' => $this->actual_end_date,
            'status' => $this->status, 'priority' => $this->priority,
            'item' => $this->whenLoaded('item', fn() => ['id' => $this->item->id, 'code' => $this->item->code, 'name' => $this->item->name]),
            'operationJobs' => $this->whenLoaded('operationJobs'),
            'created_at' => $this->created_at,
        ];
    }
}
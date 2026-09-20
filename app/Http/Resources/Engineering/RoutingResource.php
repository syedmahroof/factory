<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class RoutingResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'version' => $this->version,
            'description' => $this->description,
            'is_default' => $this->is_default,
            'operations' => $this->whenLoaded('operations', fn() => $this->operations->map(fn($op) => [
                'id' => $op->id, 'operation_no' => $op->operation_no, 'description' => $op->description,
                'work_center_id' => $op->work_center_id, 'setup_time' => $op->setup_time,
                'run_time' => $op->run_time, 'cycle_time' => $op->cycle_time,
            ])),
            'created_at' => $this->created_at,
        ];
    }
}
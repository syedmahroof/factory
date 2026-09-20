<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class WorkCenterResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'plant_id' => $this->plant_id,
            'code' => $this->code,
            'name' => $this->name,
            'capacity' => $this->capacity,
            'cost_per_hour' => $this->cost_per_hour,
            'is_active' => $this->is_active,
        ];
    }
}
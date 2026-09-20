<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class WarehouseResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'plant_id' => $this->plant_id,
            'code' => $this->code,
            'name' => $this->name,
            'type' => $this->type,
            'address' => $this->address,
            'is_active' => $this->is_active,
            'plant' => new PlantResource($this->whenLoaded('plant')),
            'created_at' => $this->created_at,
        ];
    }
}
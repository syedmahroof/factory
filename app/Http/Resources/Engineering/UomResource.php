<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class UomResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'base_uom_id' => $this->base_uom_id,
            'conversion_factor' => $this->conversion_factor,
            'is_active' => $this->is_active,
        ];
    }
}
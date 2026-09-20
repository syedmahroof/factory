<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class ItemCategoryResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'code' => $this->code,
            'description' => $this->description,
            'is_active' => $this->is_active,
        ];
    }
}
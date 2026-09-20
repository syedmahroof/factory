<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class BomResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'version' => $this->version,
            'description' => $this->description,
            'is_active' => $this->is_active,
            'item' => new ItemResource($this->whenLoaded('item')),
            'lines' => BomLineResource::collection($this->whenLoaded('lines')),
            'created_at' => $this->created_at,
        ];
    }
}
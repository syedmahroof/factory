<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class BomLineResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'quantity' => $this->quantity,
            'uom_id' => $this->uom_id,
            'scrap_percentage' => $this->scrap_percentage,
            'sequence' => $this->sequence,
            'item' => new ItemResource($this->whenLoaded('item')),
        ];
    }
}
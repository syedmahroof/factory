<?php
namespace App\Http\Resources\Engineering;
use Illuminate\Http\Resources\Json\JsonResource;
class ItemResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'category_id' => $this->category_id,
            'base_uom_id' => $this->base_uom_id,
            'standard_cost' => $this->standard_cost,
            'selling_price' => $this->selling_price,
            'purchase_price' => $this->purchase_price,
            'min_stock' => $this->min_stock,
            'max_stock' => $this->max_stock,
            'reorder_point' => $this->reorder_point,
            'lead_time_days' => $this->lead_time_days,
            'is_active' => $this->is_active,
            'is_batch_tracked' => $this->is_batch_tracked,
            'is_serial_tracked' => $this->is_serial_tracked,
            'category' => new ItemCategoryResource($this->whenLoaded('category')),
            'baseUom' => new UomResource($this->whenLoaded('baseUom')),
            'created_at' => $this->created_at,
        ];
    }
}
<?php
namespace App\Http\Resources\Maintenance;
use Illuminate\Http\Resources\Json\JsonResource;
class AssetResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'category' => $this->category, 'location' => $this->location,
            'manufacturer' => $this->manufacturer, 'model' => $this->model,
            'criticality' => $this->criticality, 'status' => $this->status,
            'purchase_cost' => $this->purchase_cost,
            'warranty_expiry' => $this->warranty_expiry,
            'created_at' => $this->created_at,
        ];
    }
}
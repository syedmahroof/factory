<?php
namespace App\Http\Resources\Inventory;
use Illuminate\Http\Resources\Json\JsonResource;
class StockMovementResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'item_id' => $this->item_id,
            'movement_type' => $this->movement_type, 'quantity' => $this->quantity,
            'batch_number' => $this->batch_number, 'serial_number' => $this->serial_number,
            'from_warehouse_id' => $this->from_warehouse_id,
            'to_warehouse_id' => $this->to_warehouse_id,
            'created_at' => $this->created_at,
        ];
    }
}
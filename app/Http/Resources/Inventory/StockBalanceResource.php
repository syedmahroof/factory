<?php
namespace App\Http\Resources\Inventory;
use Illuminate\Http\Resources\Json\JsonResource;
class StockBalanceResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'item_id' => $this->item_id,
            'warehouse_id' => $this->warehouse_id, 'lot_number' => $this->lot_number,
            'quantity' => $this->quantity, 'reserved_quantity' => $this->reserved_quantity,
            'available_quantity' => $this->available_quantity,
            'item' => $this->whenLoaded('item', fn() => ['id' => $this->item->id, 'code' => $this->item->code, 'name' => $this->item->name]),
            'warehouse' => $this->whenLoaded('warehouse', fn() => ['id' => $this->warehouse->id, 'name' => $this->warehouse->name]),
            'created_at' => $this->created_at,
        ];
    }
}
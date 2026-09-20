<?php
namespace App\Http\Resources\Procurement;
use Illuminate\Http\Resources\Json\JsonResource;
class PurchaseOrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'supplier_id' => $this->supplier_id, 'plant_id' => $this->plant_id,
            'order_date' => $this->order_date, 'expected_date' => $this->expected_date,
            'total_amount' => $this->total_amount, 'tax_amount' => $this->tax_amount,
            'net_amount' => $this->net_amount, 'status' => $this->status,
            'supplier' => new SupplierResource($this->whenLoaded('supplier')),
            'lines' => $this->whenLoaded('lines'),
            'created_at' => $this->created_at,
        ];
    }
}
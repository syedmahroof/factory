<?php
namespace App\Http\Resources\Sales;
use Illuminate\Http\Resources\Json\JsonResource;
class SalesOrderResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'customer_id' => $this->customer_id, 'order_date' => $this->order_date,
            'delivery_date' => $this->delivery_date,
            'subtotal' => $this->subtotal, 'tax_amount' => $this->tax_amount,
            'total_amount' => $this->total_amount, 'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'lines' => $this->whenLoaded('lines'),
            'created_at' => $this->created_at,
        ];
    }
}
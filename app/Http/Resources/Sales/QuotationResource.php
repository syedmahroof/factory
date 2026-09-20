<?php
namespace App\Http\Resources\Sales;
use Illuminate\Http\Resources\Json\JsonResource;
class QuotationResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'number' => $this->number,
            'customer_id' => $this->customer_id, 'quotation_date' => $this->quotation_date,
            'valid_until' => $this->valid_until, 'total_amount' => $this->total_amount,
            'status' => $this->status,
            'customer' => new CustomerResource($this->whenLoaded('customer')),
            'lines' => $this->whenLoaded('lines'),
            'created_at' => $this->created_at,
        ];
    }
}
<?php
namespace App\Http\Resources\Procurement;
use Illuminate\Http\Resources\Json\JsonResource;
class SupplierResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'contact_person' => $this->contact_person, 'email' => $this->email,
            'phone' => $this->phone, 'payment_terms' => $this->payment_terms,
            'rating' => $this->rating, 'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
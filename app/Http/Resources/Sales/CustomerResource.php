<?php
namespace App\Http\Resources\Sales;
use Illuminate\Http\Resources\Json\JsonResource;
class CustomerResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'code' => $this->code, 'name' => $this->name,
            'contact_person' => $this->contact_person, 'email' => $this->email,
            'phone' => $this->phone, 'credit_limit' => $this->credit_limit,
            'is_active' => $this->is_active, 'created_at' => $this->created_at,
        ];
    }
}
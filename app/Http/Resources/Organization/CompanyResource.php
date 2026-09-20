<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class CompanyResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'code' => $this->code,
            'name' => $this->name,
            'registration_number' => $this->registration_number,
            'tax_id' => $this->tax_id,
            'address' => $this->address,
            'city' => $this->city,
            'state' => $this->state,
            'country' => $this->country,
            'phone' => $this->phone,
            'email' => $this->email,
            'website' => $this->website,
            'base_currency' => $this->base_currency,
            'fiscal_year_start' => $this->fiscal_year_start,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
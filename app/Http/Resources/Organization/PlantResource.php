<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class PlantResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'address' => $this->address,
            'city' => $this->city,
            'timezone' => $this->timezone,
            'is_active' => $this->is_active,
            'company' => new CompanyResource($this->whenLoaded('company')),
            'created_at' => $this->created_at,
        ];
    }
}
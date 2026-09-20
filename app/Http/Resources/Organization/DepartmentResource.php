<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class DepartmentResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'code' => $this->code,
            'name' => $this->name,
            'manager_id' => $this->manager_id,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
        ];
    }
}
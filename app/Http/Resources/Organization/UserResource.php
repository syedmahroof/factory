<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class UserResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'roles' => RoleResource::collection($this->whenLoaded('roles')),
            'department_id' => $this->department_id,
            'plant_id' => $this->plant_id,
            'is_active' => $this->is_active ?? true,
            'created_at' => $this->created_at,
        ];
    }
}
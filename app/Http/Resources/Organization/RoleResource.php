<?php
namespace App\Http\Resources\Organization;
use Illuminate\Http\Resources\Json\JsonResource;
class RoleResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'permissions' => $this->whenLoaded('permissions', fn() => $this->permissions->pluck('name')),
            'created_at' => $this->created_at,
        ];
    }
}
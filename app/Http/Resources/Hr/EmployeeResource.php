<?php
namespace App\Http\Resources\Hr;
use Illuminate\Http\Resources\Json\JsonResource;
class EmployeeResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'employee_code' => $this->employee_code,
            'first_name' => $this->first_name, 'last_name' => $this->last_name,
            'email' => $this->email, 'phone' => $this->phone,
            'department_id' => $this->department_id, 'plant_id' => $this->plant_id,
            'designation' => $this->designation,
            'employment_type' => $this->employment_type,
            'hire_date' => $this->hire_date, 'status' => $this->status,
            'created_at' => $this->created_at,
        ];
    }
}
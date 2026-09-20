<?php
namespace App\Http\Resources\Hr;
use Illuminate\Http\Resources\Json\JsonResource;
class AttendanceResource extends JsonResource {
    public function toArray($request): array {
        return [
            'id' => $this->id, 'employee_id' => $this->employee_id,
            'date' => $this->date, 'shift_id' => $this->shift_id,
            'clock_in' => $this->clock_in, 'clock_out' => $this->clock_out,
            'hours_worked' => $this->hours_worked, 'status' => $this->status,
            'overtime_hours' => $this->overtime_hours,
            'employee' => $this->whenLoaded('employee', fn() => ['id' => $this->employee->id, 'name' => $this->employee->first_name . ' ' . $this->employee->last_name]),
            'created_at' => $this->created_at,
        ];
    }
}
<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class StoreTimeBookingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['employee_id' => 'required|exists:employees,id', 'production_order_id' => 'nullable|exists:production_orders,id', 'work_center_id' => 'nullable|exists:work_centers,id', 'booking_date' => 'required|date', 'start_time' => 'required|date_format:H:i', 'end_time' => 'nullable|date_format:H:i|after:start_time', 'hours' => 'required|numeric|min:0.25', 'type' => 'required|in:direct,indirect,setup,idle'];
    }
}
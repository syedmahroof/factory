<?php
namespace App\Http\Requests\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
class StoreOeeRecordRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['work_center_id' => 'required|exists:work_centers,id', 'record_date' => 'required|date', 'available_hours' => 'required|numeric|min:0', 'operating_hours' => 'required|numeric|min:0', 'downtime_hours' => 'nullable|numeric|min:0', 'total_count' => 'required|integer|min:0', 'good_count' => 'required|integer|min:0'];
    }
}
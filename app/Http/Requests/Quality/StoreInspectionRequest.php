<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreInspectionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'quality_plan_id' => 'required|exists:quality_plans,id',
            'item_id' => 'required|exists:items,id',
            'inspector_id' => 'required|exists:users,id',
            'inspection_date' => 'required|date',
            'batch_number' => 'nullable|string|max:50',
            'quantity_inspected' => 'required|numeric|min:1',
            'quantity_accepted' => 'required|numeric|min:0',
            'quantity_rejected' => 'nullable|numeric|min:0',
            'results' => 'required|array|min:1',
            'results.*.quality_characteristic_id' => 'required|exists:quality_characteristics,id',
            'results.*.value' => 'required|numeric',
            'results.*.is_pass' => 'required|boolean',
        ];
    }
}
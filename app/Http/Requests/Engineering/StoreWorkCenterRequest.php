<?php
namespace App\Http\Requests\Engineering;
use Illuminate\Foundation\Http\FormRequest;
class StoreWorkCenterRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'plant_id' => 'required|exists:plants,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:work_centers,code',
            'capacity' => 'nullable|numeric|min:0',
            'capacity_uom_id' => 'nullable|exists:uoms,id',
            'cost_per_hour' => 'nullable|numeric|min:0',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'is_active' => 'boolean',
        ];
    }
}
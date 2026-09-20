<?php
namespace App\Http\Requests\Engineering;
use Illuminate\Foundation\Http\FormRequest;
class StoreUomRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:uoms,code',
            'base_uom_id' => 'nullable|exists:uoms,id',
            'conversion_factor' => 'nullable|numeric|min:0.001',
            'is_active' => 'boolean',
        ];
    }
}
<?php
namespace App\Http\Requests\Organization;
use Illuminate\Foundation\Http\FormRequest;
class UpdatePlantRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:plants,code,' . $this->route('plant'),
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'timezone' => 'nullable|string|max:50',
            'is_active' => 'boolean',
        ];
    }
}
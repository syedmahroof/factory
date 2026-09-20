<?php
namespace App\Http\Requests\Organization;
use Illuminate\Foundation\Http\FormRequest;
class StoreDepartmentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'company_id' => 'required|exists:companies,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:departments,code',
            'manager_id' => 'nullable|exists:users,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'is_active' => 'boolean',
        ];
    }
}
<?php
namespace App\Http\Requests\Organization;
use Illuminate\Foundation\Http\FormRequest;
class UpdateCompanyRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:companies,code,' . $this->route('company'),
            'registration_number' => 'nullable|string|max:100',
            'tax_id' => 'nullable|string|max:50',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'website' => 'nullable|url|max:255',
            'base_currency' => 'nullable|string|max:10',
            'fiscal_year_start' => 'nullable|date',
            'is_active' => 'boolean',
        ];
    }
}
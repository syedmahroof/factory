<?php
namespace App\Http\Requests\Organization;
use Illuminate\Foundation\Http\FormRequest;
class StoreUserRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'role_ids' => 'nullable|array',
            'role_ids.*' => 'exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
            'plant_id' => 'nullable|exists:plants,id',
        ];
    }
}
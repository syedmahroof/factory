<?php

namespace App\Http\Requests\Account;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'user_type_id' => ['required', 'integer', Rule::in(UserType::accountTypeIds())],
            'company_id' => ['nullable', 'integer', 'exists:users,id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:50'],
            'liquidity' => ['nullable', 'numeric'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', Rule::in(array_keys(User::statusOptions()))],
        ];
    }

    public function messages(): array
    {
        return [
            'user_type_id.in' => 'That account type cannot be managed from this screen.',
        ];
    }
}

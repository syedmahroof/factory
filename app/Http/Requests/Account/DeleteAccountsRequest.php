<?php

namespace App\Http\Requests\Account;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DeleteAccountsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', new User);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => ['required', 'integer', 'exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one row to delete.',
            'ids.max' => 'Delete at most 500 accounts at a time.',
        ];
    }
}

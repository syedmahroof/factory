<?php

namespace App\Http\Requests\Bank;

use App\Models\Bank;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'account_holder_name' => ['required', 'string', 'max:255'],
            'bank_name' => ['required', 'string', 'max:255'],
            // Digits only: the blind index strips non-digits before hashing, so
            // "1234-5678" and "12345678" would otherwise be the same account
            // stored two different ways.
            'account_number' => ['required', 'string', 'regex:/^\d{4,17}$/'],
            'routing_number' => ['required', 'string', 'regex:/^\d{9}$/'],
            'account_type' => ['required', Rule::in(array_keys(Bank::accountTypeOptions()))],
            'status_id' => ['nullable', 'integer', Rule::in(array_keys(Bank::statusOptions()))],
            'debit' => ['sometimes', 'boolean'],
            'credit' => ['sometimes', 'boolean'],
            'default_debit' => ['sometimes', 'boolean'],
            'default_credit' => ['sometimes', 'boolean'],
            'verification_note' => ['nullable', 'string', 'max:1000'],
            'verify_with_actum' => ['sometimes', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'routing_number.regex' => 'A routing number is exactly 9 digits.',
            'account_number.regex' => 'An account number is 4 to 17 digits, no spaces or dashes.',
        ];
    }
}

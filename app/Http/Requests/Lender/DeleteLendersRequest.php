<?php

namespace App\Http\Requests\Lender;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DeleteLendersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('delete', new User);
    }

    public function rules(): array
    {
        return [
            'ids' => ['required', 'array', 'min:1', 'max:500'],
            'ids.*' => [
                'required',
                'integer',
                Rule::exists('users', 'id')->where('user_type_id', UserType::Lender),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'Please select at least one row to delete.',
            'ids.max' => 'Delete at most 500 lenders at a time.',
            'ids.*.exists' => 'One of the selected rows is not a lender.',
        ];
    }
}

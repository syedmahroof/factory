<?php

namespace App\Http\Requests\Account;

use Illuminate\Foundation\Http\FormRequest;

class ShowAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('account'));
    }

    public function rules(): array
    {
        return [];
    }
}

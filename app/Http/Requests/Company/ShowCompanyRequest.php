<?php

namespace App\Http\Requests\Company;

use Illuminate\Foundation\Http\FormRequest;

class ShowCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('company'));
    }

    public function rules(): array
    {
        return [];
    }
}

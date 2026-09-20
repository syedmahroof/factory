<?php

namespace App\Http\Requests\Company;

use App\Services\Company\CompanyService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListCompanyAdvancesRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [
        15,
        50,
        100,
        250,
        1000,
    ];

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->route('company'));
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'sort' => ['nullable', 'string', Rule::in(array_keys(CompanyService::SORT_COLUMNS))],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

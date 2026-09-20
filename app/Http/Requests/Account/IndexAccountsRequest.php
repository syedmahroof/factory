<?php

namespace App\Http\Requests\Account;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexAccountsRequest extends FormRequest
{
    /**
     * Columns the client may sort by.
     */
    public const SORTABLE = [
        'id',
        'name',
        'user_type_id',
        'company_id',
        'liquidity',
        'email',
        'cell_phone',
        'status_id',
    ];

    public const PER_PAGE_OPTIONS = [
        10,
        25,
        50,
        100,
    ];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'user_type_id' => ['nullable', 'integer'],
            'status_id' => ['nullable', 'integer'],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

<?php

namespace App\Http\Requests\Lender;

use App\Models\Lender;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexLendersRequest extends FormRequest
{
    /**
     * Columns the client may sort by. The profile ones are the sub-selects
     * ListLendersAction aliases onto the query.
     */
    public const SORTABLE = [
        'id',
        'name',
        'email',
        'cell_phone',
        'company_id',
        'status_id',
        'username',
        'management_fee_percentage',
        'syndication_fee_percentage',
        'syndication_fee_type',
        'lag_time_days',
    ];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'search' => ['nullable', 'string', 'max:255'],
            'status_id' => ['nullable', 'integer'],
            'company_id' => ['nullable', 'integer'],
            'syndication_fee_type' => ['nullable', 'integer', Rule::in(array_keys(Lender::syndicationFeeTypeOptions()))],
            'sort' => ['nullable', 'string', Rule::in(self::SORTABLE)],
            'direction' => ['nullable', 'string', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }
}

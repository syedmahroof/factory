<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\ManagementFeeReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Management Fee report's filter set.
 *
 * Merchants only. The legacy screen also carried an investor picker, but its query
 * never read it — the fees on this report are the advance's, not a participation's
 * — so the filter is not offered here rather than offered and ignored.
 */
class ManagementFeeReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'merchant_ids' => ['nullable', 'array'],
            'merchant_ids.*' => ['integer'],
            'search' => ['nullable', 'string', 'max:191'],
            'sort' => ['nullable', Rule::in(array_keys(ManagementFeeReportAction::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return [
            'merchant_ids' => array_filter((array) $this->input('merchant_ids', [])),
            'search' => $this->input('search'),
        ];
    }
}

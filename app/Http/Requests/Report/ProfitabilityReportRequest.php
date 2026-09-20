<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\ProfitabilityReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Profitability report's filter set.
 *
 * `bill_categories` decides what counts as a bill against the investor's earnings,
 * and `type` which share agreement the net is divided by.
 */
class ProfitabilityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            'investor_ids' => ['nullable', 'array'],
            'investor_ids.*' => ['integer'],
            'bill_categories' => ['nullable', 'array'],
            'bill_categories.*' => ['integer'],
            'type' => ['nullable', Rule::in(array_keys(ProfitabilityReportAction::SPLITS))],
            'search' => ['nullable', 'string', 'max:191'],
            'sort' => ['nullable', Rule::in(array_keys(ProfitabilityReportAction::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['to_date.after_or_equal' => 'The end of the range cannot be before the start.'];
    }

    public function filters(): array
    {
        return [
            'from_date' => $this->input('from_date'),
            'to_date' => $this->input('to_date'),
            'investor_ids' => array_filter((array) $this->input('investor_ids', [])),
            'bill_categories' => array_filter((array) $this->input('bill_categories', [])),
            'type' => $this->input('type', 'Equity'),
            'search' => $this->input('search'),
        ];
    }
}

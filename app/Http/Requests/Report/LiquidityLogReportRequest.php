<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\LiquidityLogReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filter set both liquidity log reports share — they are one query seen two
 * ways, so they take the same filters.
 */
class LiquidityLogReportRequest extends FormRequest
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
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer'],
            'investor_ids' => ['nullable', 'array'],
            'investor_ids.*' => ['integer'],
            'merchant_ids' => ['nullable', 'array'],
            'merchant_ids.*' => ['integer'],
            // Movements are labelled by what created them ("Payment", "Investment").
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['string', 'max:191'],
            'search' => ['nullable', 'string', 'max:191'],

            // The merchant view's drill-down: a batch, narrowed to one posting.
            'batch_no' => ['nullable', 'string', 'max:191'],
            'creator_id' => ['nullable', 'integer'],

            'sort' => ['nullable', Rule::in(array_unique(array_merge(
                array_keys(LiquidityLogReportAction::INVESTOR_SORTABLE),
                array_keys(LiquidityLogReportAction::MERCHANT_SORTABLE),
            )))],
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
            'company_ids' => array_filter((array) $this->input('company_ids', [])),
            'investor_ids' => array_filter((array) $this->input('investor_ids', [])),
            'merchant_ids' => array_filter((array) $this->input('merchant_ids', [])),
            'descriptions' => array_filter((array) $this->input('descriptions', [])),
            'creator_id' => $this->integer('creator_id') ?: null,
            'search' => $this->input('search'),
        ];
    }
}

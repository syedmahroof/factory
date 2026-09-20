<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\LiquidityReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Liqudity report's filter set.
 *
 * `to_date` alone, with no floor: the report is a balance as at a date, and a
 * balance has no start.
 */
class LiquidityReportRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'to_date' => ['nullable', 'date'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer'],
            'investor_ids' => ['nullable', 'array'],
            'investor_ids.*' => ['integer'],
            // The investor's own account status, unrelated to the advance statuses
            // the other reports filter on.
            'user_status_id' => ['nullable', 'integer'],
            'search' => ['nullable', 'string', 'max:191'],
            'sort' => ['nullable', Rule::in(array_keys(LiquidityReportAction::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function filters(): array
    {
        return [
            'to_date' => $this->input('to_date'),
            'company_ids' => array_filter((array) $this->input('company_ids', [])),
            'investor_ids' => array_filter((array) $this->input('investor_ids', [])),
            // InvestorWiseLiqudityReportQuerry reads this one as `status_id`.
            'status_id' => $this->integer('user_status_id') ?: null,
            'search' => $this->input('search'),
        ];
    }
}

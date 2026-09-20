<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\OverpaymentReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Overpayment report's filter set.
 *
 * MerchantHelper builds this report's `IN (...)` fragments by string
 * concatenation, so validating the ids as integers here is the second line of
 * defence behind sqlIdList().
 */
class OverpaymentReportRequest extends FormRequest
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
            'merchant_ids' => ['nullable', 'array'],
            'merchant_ids.*' => ['integer'],
            'lender_id' => ['nullable', 'integer'],
            'status_ids' => ['nullable', 'array'],
            'status_ids.*' => ['integer'],
            'search' => ['nullable', 'string', 'max:191'],
            'sort' => ['nullable', Rule::in(array_keys(OverpaymentReportAction::SORTABLE))],
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
            // The dated query switches on this; the legacy screen offered no picker
            // for it, so it is always the payment date.
            'based_on' => 'payment_date',
            'from_date' => $this->input('from_date'),
            'to_date' => $this->input('to_date'),
            'merchant_ids' => array_filter((array) $this->input('merchant_ids', [])),
            'lender_id' => $this->integer('lender_id') ?: null,
            'status_ids' => array_filter((array) $this->input('status_ids', [])),
            'search' => $this->input('search'),
        ];
    }
}

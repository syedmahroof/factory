<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\PaymentDetailsReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Payment Details report's filter set — the widest of the reports, because it
 * is the one operators use to find a particular payment.
 *
 * The id lists matter more than they look. MerchantHelper builds this report's
 * `IN (...)` fragments by string concatenation, so validating them as integers
 * here is the second line of defence behind sqlIdList() in the helper itself.
 */
class PaymentDetailsReportRequest extends FormRequest
{
    /** The columns the date range can apply to, as the legacy screen offered them. */
    public const BASED_ON = ['payment_date', 'created_at'];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'based_on' => ['nullable', Rule::in(self::BASED_ON)],
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],

            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer'],
            'investor_ids' => ['nullable', 'array'],
            'investor_ids.*' => ['integer'],
            'merchant_ids' => ['nullable', 'array'],
            'merchant_ids.*' => ['integer'],
            'lender_id' => ['nullable', 'integer'],
            'status_ids' => ['nullable', 'array'],
            'status_ids.*' => ['integer'],

            // What this report narrows by and the others do not.
            'payment_mode_ids' => ['nullable', 'array'],
            'payment_mode_ids.*' => ['integer'],
            'rcode_ids' => ['nullable', 'array'],
            'rcode_ids.*' => ['integer'],
            'min_amount' => ['nullable', 'numeric'],
            // Only compared when there is a floor to compare against; on its own a
            // ceiling is a perfectly good filter.
            'max_amount' => ['nullable', 'numeric', Rule::when($this->filled('min_amount'), ['gte:min_amount'])],
            'remarks' => ['nullable', 'string', 'max:191'],

            'search' => ['nullable', 'string', 'max:191'],

            // The drill-down's parent.
            'merchant_payment_id' => ['nullable', 'integer'],

            'sort' => ['nullable', Rule::in(array_keys(PaymentDetailsReportAction::SORTABLE))],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],
            // A bound, not the screen's dropdown: PER_PAGE_OPTIONS is what the page
            // length menu offers, and pinning validation to it would refuse a
            // perfectly reasonable size asked for by anything else.
            'per_page' => ['nullable', 'integer', 'min:1', 'max:1000'],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['to_date.after_or_equal' => 'The end of the range cannot be before the start.'];
    }

    /**
     * The shape MerchantHelper expects — it reads `$request['investor_ids']` and
     * friends straight off the request it is handed.
     */
    public function filters(): array
    {
        return [
            'based_on' => $this->input('based_on', 'payment_date'),
            'from_date' => $this->input('from_date'),
            'to_date' => $this->input('to_date'),
            'company_ids' => array_filter((array) $this->input('company_ids', [])),
            'investor_ids' => array_filter((array) $this->input('investor_ids', [])),
            'merchant_ids' => array_filter((array) $this->input('merchant_ids', [])),
            'lender_id' => $this->integer('lender_id') ?: null,
            'status_ids' => array_filter((array) $this->input('status_ids', [])),
            'payment_mode_ids' => array_filter((array) $this->input('payment_mode_ids', [])),
            'rcode_ids' => array_filter((array) $this->input('rcode_ids', [])),
            'min_amount' => $this->input('min_amount'),
            'max_amount' => $this->input('max_amount'),
            'remarks' => $this->input('remarks'),
            'search' => $this->input('search'),
        ];
    }
}

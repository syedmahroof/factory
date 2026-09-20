<?php

namespace App\Http\Requests\Report;

use App\Actions\Report\PaymentReportAction;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The Merchant Payment report's filter set.
 *
 * The id lists matter more than they look. MerchantHelper builds this report's
 * `IN (...)` fragments by string concatenation, so validating them as integers
 * here is the second line of defence behind sqlIdList() in the helper itself.
 */
class PaymentReportRequest extends FormRequest
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
            'search' => ['nullable', 'string', 'max:191'],

            // The drill-down's parent: a merchant is a users row, so this is a
            // users.id and not a merchants.id.
            'merchant_id' => ['nullable', 'integer'],

            'sort' => ['nullable', Rule::in(array_keys(PaymentReportAction::SORTABLE))],
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
            'search' => $this->input('search'),
        ];
    }
}

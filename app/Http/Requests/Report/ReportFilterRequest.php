<?php

namespace App\Http\Requests\Report;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * The filter set every report screen shares.
 *
 * The ids matter more than they look. The report queries build `IN (...)`
 * fragments by string concatenation, so before `sqlIdList()` existed a value like
 * `1) OR 1=1 -- ` posted into `investor_ids` rewrote the WHERE clause and returned
 * the whole book. Validating them as integers here is the second line of defence;
 * `sqlIdList()` in the helper is the first.
 */
class ReportFilterRequest extends FormRequest
{
    public const PER_PAGE_OPTIONS = [10, 25, 50, 100, 250];

    public const BASED_ON = ['payment_date', 'created_at', 'funded_date', 'changed_date', 'last_payment_date'];

    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [
            'from_date' => ['nullable', 'date'],
            'to_date' => ['nullable', 'date', 'after_or_equal:from_date'],
            // The column the date range applies to. Which values mean anything
            // depends on the report — the helpers switch on this string — so the
            // set here is the union of what the screens offer.
            'based_on' => ['nullable', Rule::in(self::BASED_ON)],

            'investor_ids' => ['nullable', 'array'],
            'investor_ids.*' => ['integer'],
            'company_ids' => ['nullable', 'array'],
            'company_ids.*' => ['integer'],
            'merchant_ids' => ['nullable', 'array'],
            'merchant_ids.*' => ['integer'],
            'lender_id' => ['nullable', 'integer'],
            'status_ids' => ['nullable', 'array'],
            'status_ids.*' => ['integer'],
            'bill_categories' => ['nullable', 'array'],
            'bill_categories.*' => ['integer'],

            // Payment-details narrows further than the other reports do.
            'payment_mode_ids' => ['nullable', 'array'],
            'payment_mode_ids.*' => ['integer'],
            'rcode_ids' => ['nullable', 'array'],
            'rcode_ids.*' => ['integer'],
            'min_amount' => ['nullable', 'numeric'],
            // Only compared when there is a floor to compare against; on its own a
            // ceiling is a perfectly good filter.
            'max_amount' => ['nullable', 'numeric', Rule::when($this->filled('min_amount'), ['gte:min_amount'])],
            'remarks' => ['nullable', 'string', 'max:191'],

            // Liquidity-log rows are labelled by what created them ("Payment",
            // "Investment", ...), and the log report filters on that label.
            'descriptions' => ['nullable', 'array'],
            'descriptions.*' => ['string', 'max:191'],
            // The liquidity report filters investors by their own account status,
            // which is a single value and unrelated to the advance statuses above.
            'user_status_id' => ['nullable', 'integer'],

            // Drill-down parents. Which one is required depends on the report, so
            // the controller checks presence; these only pin the shape.
            'merchant_id' => ['nullable', 'integer'],
            'merchant_payment_id' => ['nullable', 'integer'],
            'batch_no' => ['nullable', 'string', 'max:191'],
            'creator_id' => ['nullable', 'integer'],

            // Profitability's split agreement.
            'type' => ['nullable', 'string', 'max:32'],

            // The per-report allow-list in RunReportAction decides whether a sort
            // column is actually usable; this only keeps junk out of the builder.
            'sort' => ['nullable', 'string', 'max:64'],
            'direction' => ['nullable', Rule::in(['asc', 'desc'])],

            'per_page' => ['nullable', 'integer', Rule::in(self::PER_PAGE_OPTIONS)],
            'page' => ['nullable', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return ['to_date.after_or_equal' => 'The end of the range cannot be before the start.'];
    }

    /**
     * The shape the legacy helpers expect — they read `$request['investor_ids']`
     * and friends off the request directly.
     */
    public function filters(): array
    {
        return [
            'from_date' => $this->input('from_date'),
            'to_date' => $this->input('to_date'),
            // Left null when unset so each helper applies its own default column.
            'based_on' => $this->input('based_on'),
            'investor_ids' => array_filter((array) $this->input('investor_ids', [])),
            'company_ids' => array_filter((array) $this->input('company_ids', [])),
            'merchant_ids' => array_filter((array) $this->input('merchant_ids', [])),
            'lender_id' => $this->integer('lender_id') ?: null,
            'status_ids' => array_filter((array) $this->input('status_ids', [])),
            'bill_categories' => array_filter((array) $this->input('bill_categories', [])),
            'descriptions' => array_filter((array) $this->input('descriptions', [])),
            'payment_mode_ids' => array_filter((array) $this->input('payment_mode_ids', [])),
            'rcode_ids' => array_filter((array) $this->input('rcode_ids', [])),
            'min_amount' => $this->input('min_amount'),
            'max_amount' => $this->input('max_amount'),
            'remarks' => $this->input('remarks'),
            // InvestorWiseLiqudityReportQuerry reads this one as `status_id`.
            'status_id' => $this->integer('user_status_id') ?: null,
        ];
    }
}

<?php

namespace App\Actions\Report;

use App\Helpers\Facades\MerchantHelper;
use App\Models\Merchant;
use App\Models\MerchantPayment;
use App\Models\MerchantPaymentInvestor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * The Payment Details report: one row per payment taken, with the split it was
 * divided into collapsed on to it.
 *
 * Where the Payment report answers "how is this advance doing", this one answers
 * "what happened on this date" — the same money seen per transaction rather than
 * per advance, and it narrows much further: by payment mode, by ACH return code,
 * by amount, by what the operator typed in the remarks.
 *
 * MerchantHelper owns the query, and unlike the other reports it already selects
 * its own columns — including the fee cascade summed over the participants. What
 * lives here is the rest of what the legacy DataTable controller did: the sort
 * allow-list, the paging, the footer totals and the per-payment investor split.
 */
class PaymentDetailsReportAction
{
    /**
     * Sortable column => the expression to order by.
     *
     * Allow-listed rather than passed through: Laravel would backtick whatever it
     * is given, so this is not an injection route, but an arbitrary column is
     * still not the client's to choose.
     */
    public const SORTABLE = [
        'id' => 'merchant_payments.id',
        'date' => 'merchant_payments.date',
        'created_at' => 'merchant_payments.created_at',
        'merchant_name' => 'merchant_users.name',
        'lender_name' => 'lenders.name',
        'status_id' => 'merchants.status_id',
        'payment_mode_id' => 'merchant_payments.payment_mode_id',
        'rcode' => 'rcodes.code',
        'debited' => 'merchant_payments.amount',
        'investor_count' => 'investor_count',
        'participant_share' => 'participant_share',
        'management_fee' => 'management_fee',
        'agent_fee' => 'agent_fee',
        'net_amount' => 'net_amount',
        'principal' => 'principal',
        'profit' => 'profit',
        'remarks' => 'merchant_payments.remarks',
        'creator_name' => 'creators.name',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = ['debited', 'participant_share', 'management_fee', 'agent_fee', 'net_amount', 'principal', 'profit'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, unordered and unpaged.
     *
     * The helper applies every filter, including the investor and company ones —
     * those narrow the join itself, so the summed participant columns hold only
     * the shares that were asked for rather than the payment's whole split.
     */
    public function query(array $filters): Builder
    {
        return MerchantHelper::PaymentDetailsReportQuerry(new Request($filters))
            // The grid's own search box, which legacy filtered on the merchant name.
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('merchant_users.name', 'LIKE', '%'.$v.'%'))
            // The advance's own id, which admin.merchants.show binds. The helper
            // already joins merchants; the merchant_id it selects is the merchant's
            // users.id, and linking the name with that one 404s.
            ->addSelect('merchants.id as merchant_row_id');
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $column = self::SORTABLE[$sort] ?? 'merchant_payments.date';

        return $this->query($filters)
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            // A shared payment date is not an order; without a tiebreaker the same
            // row can appear on two pages and be missing from a third.
            ->orderByDesc('merchant_payments.id')
            ->paginate($perPage)
            ->through(fn ($row) => $this->present($row));
    }

    /**
     * Column totals over every row the filter matched, not just the visible page.
     *
     * Through the helper, which sums over the grouped rows rather than the joined
     * ones — a payment split five ways would otherwise be counted five times.
     */
    public function totals(array $filters): array
    {
        $row = MerchantHelper::PaymentDetailsReportTotals($this->query($filters));

        return collect(self::TOTALS)
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();
    }

    /**
     * How one payment was split: the fee cascade, per investor.
     *
     * Scoped to a single payment, so it returns every participant rather than
     * paging — the longest is one advance's investor list.
     */
    public function investors(int $paymentId, array $filters): Collection
    {
        return MerchantPaymentInvestor::query()
            ->join('users as investors', 'investors.id', 'merchant_payment_investors.investor_id')
            ->leftJoin('users as companies', 'companies.id', 'merchant_payment_investors.company_id')
            ->leftJoin('rcodes', 'rcodes.id', 'merchant_payment_investors.rcode_id')
            ->where('merchant_payment_investors.merchant_payment_id', $paymentId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.investor_id', $v))
            ->orderBy('investors.name')
            ->select(
                'merchant_payment_investors.id',
                'merchant_payment_investors.investor_id',
                'investors.name as investor_name',
                'companies.name as company_name',
                'merchant_payment_investors.date',
                'rcodes.code as rcode',
                'merchant_payment_investors.amount as participant_share',
                'merchant_payment_investors.management_fee',
                'merchant_payment_investors.agent_fee',
                'merchant_payment_investors.net_amount',
                'merchant_payment_investors.principal',
                'merchant_payment_investors.profit',
            )
            ->get()
            ->map(function ($row) {
                foreach (['participant_share', 'management_fee', 'agent_fee', 'net_amount', 'principal', 'profit'] as $column) {
                    $row->$column = (float) $row->$column;
                }

                return $row;
            });
    }

    /**
     * The filtered query as PaymentDetailsExport wants it — that export maps the
     * helper's own aliases, so it is given them unrenamed.
     */
    public function exportQuery(array $filters): Builder
    {
        return $this->query($filters);
    }

    /**
     * One row as the screen wants it.
     *
     * The helper names four of its columns in capitals — `Merchant`, `Lender`,
     * `Creator`, `RCode` — because the legacy DataTable addressed them that way.
     * The screen addresses columns by key, so they are renamed here rather than in
     * the query, which the Excel export still reads as it is.
     */
    private function present(object $row): object
    {
        $row->merchant_name = $row->Merchant;
        $row->lender_name = $row->Lender;
        $row->creator_name = $row->Creator;
        $row->rcode = $row->RCode;

        // Read off the maps rather than through the model accessors, which assume
        // every stored id is still one of the declared options.
        $row->status = Merchant::statusOptions()[$row->status_id] ?? '';
        $row->payment_mode = MerchantPayment::paymentModeOptions()[$row->payment_mode_id] ?? '';

        foreach (['debited', 'participant_share', 'management_fee', 'agent_fee', 'net_amount', 'principal', 'profit'] as $column) {
            $row->$column = (float) $row->$column;
        }

        $row->investor_count = (int) $row->investor_count;

        unset($row->Merchant, $row->Lender, $row->Creator, $row->RCode);

        return $row;
    }
}

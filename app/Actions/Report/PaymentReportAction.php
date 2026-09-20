<?php

namespace App\Actions\Report;

use App\Helpers\Facades\MerchantHelper;
use App\Models\Merchant;
use App\Models\MerchantPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Merchant Payment report: one row per funded advance, carrying what was
 * debited from the merchant and how far the split has repaid its investors.
 *
 * The filtering is MerchantHelper's — the same query the legacy screen ran, kept
 * because it is large, joined and well understood. What lives here is everything
 * the legacy DataTable controller did on top of it and v3 had lost: the join to
 * users for the merchant name, the curated SELECT with its two derived balances,
 * the allow-listed sort, the paging, the footer totals and the per-merchant
 * payment history behind each row.
 */
class PaymentReportAction
{
    /**
     * Sortable column => the expression to order by.
     *
     * Allow-listed rather than passed through: Laravel would backtick whatever it
     * is given, so this is not an injection route, but an arbitrary column is
     * still not the client's to choose. The aliases are ordered on by name, which
     * MySQL resolves against the select list.
     */
    public const SORTABLE = [
        'merchant_name' => 'users.name',
        'user_id' => 'merchants.user_id',
        'status_id' => 'merchants.status_id',
        'funded_date' => 'merchants.funded_date',
        'last_payment_date' => 'merchants.last_payment_date',
        'last_payment_amount' => 'merchants.last_payment_amount',
        'debited' => 'debited',
        'total_payment' => 'total_payment',
        'management_fee' => 'management_fee',
        'net_amount' => 'net_amount',
        'principal' => 'principal',
        'profit' => 'profit',
        'investor_rtr' => 'investor_rtr',
        'net_zero_balance' => 'net_zero_balance',
        'investor_rtr_balance' => 'investor_rtr_balance',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = ['debited', 'total_payment', 'management_fee', 'net_amount', 'principal', 'profit'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, shaped but unordered and unpaged.
     *
     * getPaymentReportData() swaps in a different, date-aware query as soon as a
     * range is given — the undated one reads the roll-up columns on
     * merchant_investors, the dated one re-aggregates merchant_payments over the
     * range. Both expose the same aliases, which is why one SELECT covers them,
     * and going through the switch is what makes the date filter mean anything.
     */
    public function query(array $filters): Builder
    {
        return $this->filtered($filters)
            ->join('users', 'users.id', 'merchants.user_id')
            // The grid's own search box, which legacy filtered on the merchant name.
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('users.name', 'LIKE', '%'.$v.'%'))
            ->select(
                'users.name as merchant_name',
                'merchants.user_id',
                // The advance's own id, which admin.merchants.show binds. user_id is
                // the merchant's users.id — linking with that one 404s.
                'merchants.id as merchant_row_id',
                'merchants.status_id',
                'merchants.funded_date',
                'merchants.last_payment_date',
                'merchants.last_payment_amount',
                'debited',
                'total_payment',
                'management_fee',
                'net_amount',
                'principal',
                'profit',
                'investor_rtr',
                'invested',
                // Neither is stored. Net zero is what the investors are still short
                // of their principal; the RTR balance is the same against what the
                // advance owes them in full. Both floor at zero — an advance that
                // has repaid more than either is not owed a negative amount.
                DB::raw('IF(invested-net_amount>0,invested-net_amount,0) as net_zero_balance'),
                DB::raw('IF(investor_rtr-total_payment>0,investor_rtr-total_payment,0) as investor_rtr_balance'),
            );
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $query = $this->query($filters);

        $column = self::SORTABLE[$sort] ?? 'users.name';

        return $query
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->through(fn ($row) => $this->present($row));
    }

    /**
     * Column totals over every row the filter matched, not just the visible page.
     *
     * Summed through a wrapped sub-select rather than by calling sum() on the
     * query, because the report's own HAVING filters on a select alias: legacy's
     * `sum('debited')` dropped the select list, which took the alias with it, and
     * totalled every advance rather than the ones on show.
     */
    public function totals(array $filters): array
    {
        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'payment_report')
            ->selectRaw(implode(',', array_map(fn ($c) => "SUM($c) as $c", self::TOTALS)))
            ->first();

        return collect(self::TOTALS)
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();
    }

    /**
     * Every payment taken on one advance, with its split collapsed to totals.
     *
     * The child table behind a row. Scoped to a single merchant, so it returns all
     * of them rather than paging — the longest is one advance's payment history.
     */
    public function history(int $merchantId, array $filters): Collection
    {
        $basedOn = ($filters['based_on'] ?? 'payment_date') === 'created_at' ? 'created_at' : 'date';

        return MerchantPayment::query()
            ->join('merchant_payment_investors', 'merchant_payment_investors.merchant_payment_id', 'merchant_payments.id')
            ->join('users as investors', 'merchant_payment_investors.investor_id', 'investors.id')
            ->where('merchant_payments.merchant_id', $merchantId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.investor_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $q->whereDate("merchant_payments.$basedOn", '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $q->whereDate("merchant_payments.$basedOn", '<=', $v))
            ->groupBy('merchant_payments.id')
            ->orderByDesc('merchant_payments.date')
            ->select(
                'merchant_payments.id',
                'merchant_payments.date',
                'merchant_payment_investors.merchant_id',
                'merchant_payments.amount as debited',
                DB::raw('sum(merchant_payment_investors.amount) as participant_share'),
                DB::raw('sum(merchant_payment_investors.management_fee) as management_fee'),
                DB::raw('sum(merchant_payment_investors.net_amount) as net_amount'),
                DB::raw('GROUP_CONCAT(investors.name) as investors'),
            )
            ->get()
            // The legacy child table rendered the concatenation as a list of names.
            ->map(fn ($row) => tap($row, fn ($r) => $r->investors = array_values(array_filter(
                array_map('trim', explode(',', (string) $r->investors)),
            ))));
    }

    /**
     * The filtered query before this report shapes it — what the Excel export
     * wants, since PaymentExport applies its own join and select.
     */
    public function exportQuery(array $filters): Builder
    {
        return $this->filtered($filters);
    }

    private function filtered(array $filters): Builder
    {
        // The helper reads its filters off a request object by array access.
        return MerchantHelper::getPaymentReportData(new Request($filters));
    }

    /**
     * One row as the screen wants it: the status spelled out, the money cast off
     * the driver's strings so the client is not formatting text as numbers.
     */
    private function present(object $row): object
    {
        // Read off the map rather than through the model accessor, which assumes
        // every stored status_id is still one of the declared options.
        $row->status = Merchant::statusOptions()[$row->status_id] ?? '';

        foreach (['last_payment_amount', 'debited', 'total_payment', 'management_fee', 'net_amount',
            'principal', 'profit', 'investor_rtr', 'invested', 'net_zero_balance', 'investor_rtr_balance'] as $column) {
            $row->$column = (float) $row->$column;
        }

        return $row;
    }
}

<?php

namespace App\Actions\Report;

use App\Helpers\Facades\MerchantHelper;
use App\Models\MerchantInvestor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The Investment report: one row per advance, showing what was syndicated into it
 * rather than what has come back out.
 *
 * The mirror of the Payment report — same advances, the other side of the ledger.
 * `invested` is the participation plus its investment fees, `share` the sum of the
 * percentages taken, and `anticipated_management_fee` what the house expects to
 * earn if the advance runs to term: rtr × management_fee_percentage, which is a
 * projection rather than anything collected.
 *
 * MerchantHelper owns the filtering. What lives here is what the legacy DataTable
 * controller did on top: the join to users for the name, the curated SELECT, the
 * allow-listed sort, the paging, the footer totals, and the participants behind
 * each row.
 */
class InvestmentReportAction
{
    /**
     * Sortable column => the expression to order by.
     *
     * Allow-listed rather than passed through: Laravel would backtick whatever it
     * is given, so this is not an injection route, but an arbitrary column is
     * still not the client's to choose.
     */
    public const SORTABLE = [
        'user_id' => 'merchants.user_id',
        'merchant_name' => 'users.name',
        'funded_date' => 'merchants.funded_date',
        'funded_amount' => 'funded_amount',
        'rtr' => 'rtr',
        'investment_fee_amount' => 'investment_fee_amount',
        'share' => 'share',
        'invested' => 'invested',
        'anticipated_management_fee' => 'anticipated_management_fee',
        'created_at' => 'created_at',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = ['funded_amount', 'rtr', 'investment_fee_amount', 'invested', 'anticipated_management_fee'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, shaped but unordered and unpaged.
     *
     * The date range applies either to when the advance was funded or to when the
     * participations were written, which are not the same question — an advance
     * funded in March can still be syndicated in April.
     */
    public function query(array $filters): Builder
    {
        return $this->filtered($filters)
            ->join('users', 'users.id', 'merchants.user_id')
            // The grid's own search box, which legacy filtered on the merchant name.
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('users.name', 'LIKE', '%'.$v.'%'))
            ->select(
                'merchants.user_id',
                // The advance's own id, which admin.merchants.show binds. user_id is
                // the merchant's users.id — linking with that one 404s.
                'merchants.id as merchant_row_id',
                'users.name as merchant_name',
                'merchants.funded_date',
                'funded_amount',
                'merchant_investors.rtr',
                'investment_fee_amount',
                'share',
                'invested',
                'anticipated_management_fee',
                'merchant_investors.created_at',
            );
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $column = self::SORTABLE[$sort] ?? 'merchants.user_id';

        return $this->query($filters)
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->through(fn ($row) => $this->present($row));
    }

    /**
     * Column totals over every row the filter matched, not just the visible page.
     *
     * Summed through a wrapped sub-select rather than by calling sum() on the
     * query, because the report's own HAVING filters on a select alias: dropping
     * the select list to aggregate would take the alias with it.
     */
    public function totals(array $filters): array
    {
        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'investment_report')
            ->selectRaw(implode(',', array_map(fn ($c) => "SUM($c) as $c", self::TOTALS)))
            ->first();

        return collect(self::TOTALS)
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();
    }

    /**
     * Who syndicated one advance, and on what terms.
     *
     * Scoped to a single advance, so it returns every participation rather than
     * paging — the longest is one advance's investor list.
     */
    public function investors(int $merchantId, array $filters): Collection
    {
        $basedOn = ($filters['based_on'] ?? 'funded_date') === 'created_at' ? 'created_at' : 'funded_date';

        return MerchantInvestor::query()
            ->join('merchants', 'merchant_investors.merchant_id', 'merchants.user_id')
            ->join('users as investors', 'investors.id', 'merchant_investors.investor_id')
            ->where('merchant_investors.merchant_id', $merchantId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.investor_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $basedOn === 'funded_date'
                ? $q->whereDate('merchants.funded_date', '>=', $v)
                : $q->whereDate('merchant_investors.created_at', '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $basedOn === 'funded_date'
                ? $q->whereDate('merchants.funded_date', '<=', $v)
                : $q->whereDate('merchant_investors.created_at', '<=', $v))
            ->groupBy('merchant_investors.id')
            ->orderBy('investors.name')
            ->select(
                'merchant_investors.id',
                'merchant_investors.investor_id',
                'investors.name as investor_name',
                'merchant_investors.funded',
                'merchant_investors.rtr',
                'merchant_investors.investment_fee_amount',
                'merchant_investors.share',
                'merchant_investors.invested',
                'merchant_investors.paid_management_fee',
            )
            ->get()
            ->map(function ($row) {
                foreach (['funded', 'rtr', 'investment_fee_amount', 'share', 'invested', 'paid_management_fee'] as $column) {
                    $row->$column = (float) $row->$column;
                }

                return $row;
            });
    }

    /**
     * The filtered query before this report shapes it — what the Excel export
     * wants, since InvestmentExport applies its own select and reads the merchant
     * name off the relation.
     */
    public function exportQuery(array $filters): Builder
    {
        return $this->filtered($filters);
    }

    private function filtered(array $filters): Builder
    {
        // The helper reads its filters off a request object by array access.
        return MerchantHelper::InvestmentReportQuerry(new Request($filters));
    }

    /** One row as the screen wants it, the money cast off the driver's strings. */
    private function present(object $row): object
    {
        foreach (['funded_amount', 'rtr', 'investment_fee_amount', 'share', 'invested', 'anticipated_management_fee'] as $column) {
            $row->$column = (float) $row->$column;
        }

        return $row;
    }
}

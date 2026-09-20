<?php

namespace App\Actions\Report;

use App\Models\LiquidityLog;
use App\Models\Merchant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The two liquidity log reports, which are one query seen two ways.
 *
 * The investor view is the log itself: one row per investor per movement, which is
 * how the jobs write it. The merchant view collapses those into the postings that
 * caused them — a batch of ninety rows becomes the one Payment that produced it —
 * and drills back down into the investors it moved money for.
 */
class LiquidityLogReportAction
{
    public const INVESTOR_SORTABLE = [
        'created_at' => 'liquidity_logs.created_at',
        'investor_name' => 'investors.name',
        'merchant_name' => 'merchants.name',
        'amount' => 'amount',
        'net_liquidity' => 'net_liquidity',
        'description' => 'description',
    ];

    public const MERCHANT_SORTABLE = [
        'created_at' => 'created_at',
        'batch_no' => 'batch_no',
        'merchant_name' => 'merchant_name',
        'amount' => 'amount',
        'description' => 'description',
    ];

    /** The column the footer sums on both views. */
    public const TOTALS = ['amount'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /** Every movement, one row per investor per event. */
    public function investorQuery(array $filters): Builder
    {
        return $this->base($filters)
            ->join('users as investors', 'investors.id', 'investor_id')
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('investors.name', 'LIKE', '%'.$v.'%'))
            ->select(
                'liquidity_logs.id',
                'liquidity_logs.created_at',
                'investors.name as investor_name',
                'investor_id',
                'merchants.name as merchant_name',
                'merchant_id',
                'amount',
                'net_liquidity',
                'description',
            )
            // merchants.id, which admin.merchants.show binds. liquidity_logs.merchant_id
            // is the merchant's users.id — a different number, and linking the name
            // with it lands on the wrong advance or on nothing.
            ->addSelect(['merchant_row_id' => Merchant::rowIdFor('liquidity_logs.merchant_id')]);
    }

    /**
     * The same movements collapsed to one row per posting.
     *
     * Two deviations from legacy, both because legacy grouped on `batch_no` alone:
     *
     * A batch number is not unique on its own — an Investment and a Payment posted
     * in the same batch share it — so this groups on `(batch_no, description,
     * creator_id)`. That splits a mixed batch into its two real postings instead of
     * summing them into one mislabelled row.
     *
     * And a batch is not one merchant: legacy selected `merchants.name` anyway and
     * MySQL handed back whichever row it felt like, so the column named an
     * arbitrary advance out of forty-odd. Here the name is only given when the
     * posting really does concern a single merchant, and the count rides alongside.
     * Column totals are unchanged throughout.
     */
    public function merchantQuery(array $filters): Builder
    {
        return $this->base($filters)
            ->whereNotNull('merchant_id')
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('merchants.name', 'LIKE', '%'.$v.'%'))
            ->groupBy('batch_no', 'description', 'creator_id')
            ->select(
                DB::raw('max(liquidity_logs.id) as id'),
                DB::raw('max(liquidity_logs.created_at) as created_at'),
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN max(merchants.name) END as merchant_name'),
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN max(merchant_id) END as merchant_id'),
                // The advance behind the posting, for the same link the investor view
                // carries — and named on the same condition as the merchant beside it,
                // since a posting spanning forty advances has no single one to open.
                // Correlated on max(merchant_id) rather than through a join to
                // merchants: a merchant can hold more than one advance now, and a join
                // would duplicate the log rows this row sums.
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN (SELECT deal.id FROM merchants as deal WHERE deal.user_id = max(liquidity_logs.merchant_id) AND deal.deleted_at IS NULL ORDER BY deal.id DESC LIMIT 1) END as merchant_row_id'),
                DB::raw('count(distinct merchant_id) as merchant_count'),
                DB::raw('sum(amount) as amount'),
                'description',
                'batch_no',
                'creator_id',
            );
    }

    public function paginate(string $view, array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $merchant = $view === 'merchant';
        $sortable = $merchant ? self::MERCHANT_SORTABLE : self::INVESTOR_SORTABLE;
        $column = $sortable[$sort] ?? ($merchant ? 'created_at' : 'liquidity_logs.created_at');

        return ($merchant ? $this->merchantQuery($filters) : $this->investorQuery($filters))
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->through(function ($row) {
                $row->amount = (float) $row->amount;

                if (isset($row->net_liquidity)) {
                    $row->net_liquidity = (float) $row->net_liquidity;
                }

                return $row;
            });
    }

    public function totals(string $view, array $filters): array
    {
        $query = $view === 'merchant' ? $this->merchantQuery($filters) : $this->investorQuery($filters);

        $row = DB::query()
            ->fromSub($query->toBase(), 'liquidity_log_report')
            ->selectRaw('SUM(amount) as amount')
            ->first();

        return ['amount' => round((float) ($row->amount ?? 0), 2)];
    }

    /**
     * The investors one posting moved money for.
     *
     * A batch number is shared by the Investment and the Payment posted in it, so
     * the row's own description and creator narrow it to the one posting.
     */
    public function batchInvestors(string $batchNo, array $filters): Collection
    {
        return LiquidityLog::query()
            ->join('users as investors', 'investors.id', 'liquidity_logs.investor_id')
            ->where('batch_no', $batchNo)
            // The row this opened from counts only merchant-attributed movements,
            // so the child has to as well or it sums to more than its own parent.
            // Legacy left this off and the two disagreed on any posting that also
            // moved money not tied to an advance.
            ->whereNotNull('merchant_id')
            ->when($filters['descriptions'] ?? [], fn ($q, $v) => $q->whereIn('liquidity_logs.description', $v))
            ->when($filters['creator_id'] ?? null, fn ($q, $v) => $q->where('liquidity_logs.creator_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('investor_id', $v))
            ->when($filters['merchant_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_id', $v))
            ->orderBy('investors.name')
            ->select(
                'liquidity_logs.id',
                'liquidity_logs.investor_id',
                'investors.name as investor_name',
                'liquidity_logs.amount',
                'liquidity_logs.net_liquidity',
            )
            ->get()
            ->map(function ($row) {
                $row->amount = (float) $row->amount;
                $row->net_liquidity = (float) $row->net_liquidity;

                return $row;
            });
    }

    /** The filters both views share. */
    private function base(array $filters): Builder
    {
        return LiquidityLog::query()
            ->leftJoin('users as merchants', 'merchants.id', 'merchant_id')
            ->when($filters['descriptions'] ?? [], fn ($q, $v) => $q->whereIn('liquidity_logs.description', $v))
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('liquidity_logs.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('investor_id', $v))
            ->when($filters['merchant_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $q->whereDate('liquidity_logs.created_at', '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $q->whereDate('liquidity_logs.created_at', '<=', $v));
    }
}

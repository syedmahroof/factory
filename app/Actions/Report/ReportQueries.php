<?php

namespace App\Actions\Report;

use App\Models\LiquidityLog;
use App\Models\Merchant;
use App\Models\MerchantStatusLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * The report queries that never made it into a helper.
 *
 * Payment, investment, default-rate and overpayment all live in MerchantHelper and
 * the liquidity roll-up lives in InvestorHelper, but these four were written inline
 * in the legacy DataTable controller methods. They are reproduced here so that
 * RunReportAction has one place to look, and so the screens stop being the only
 * definition of the query.
 *
 * Each returns a builder — filtering only. Selecting, sorting and paging are the
 * action's job.
 */
class ReportQueries
{
    /**
     * Every status transition an advance has been through.
     */
    public static function merchantStatusLog(array $filters): Builder
    {
        return MerchantStatusLog::query()
            ->join('users as merchants', 'merchants.id', 'merchant_id')
            ->join('users as creator', 'creator.id', 'creator_id')
            // A status filter matches either end of the transition: asking for
            // "Default" means both the deals that entered it and the ones that left.
            ->when($filters['status_ids'] ?? [], fn ($q, $v) => $q->where(function ($q) use ($v) {
                $q->whereIn('old_status_id', $v)->orWhereIn('new_status_id', $v);
            }))
            ->when($filters['merchant_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $q->whereDate('merchant_status_logs.created_at', '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $q->whereDate('merchant_status_logs.created_at', '<=', $v))
            ->orderByDesc('merchant_status_logs.id')
            ->select(
                'merchant_status_logs.id',
                'merchants.name as merchant_name',
                'creator.name as creator_name',
                'merchant_status_logs.merchant_id',
                'merchant_status_logs.old_status_id',
                'merchant_status_logs.new_status_id',
                'merchant_status_logs.creator_id',
                'merchant_status_logs.created_at',
            )
            // merchants.id, which admin.merchants.show binds — merchant_id is the
            // merchant's users.id, and the two are different numbers.
            ->addSelect(['merchant_row_id' => Merchant::rowIdFor('merchant_status_logs.merchant_id')]);
    }

    /**
     * Every liquidity movement, one row per investor per event.
     */
    public static function investorLiquidityLog(array $filters): Builder
    {
        return self::liquidityLogBase($filters)
            ->join('users as investors', 'investors.id', 'investor_id')
            ->orderByDesc('liquidity_logs.id')
            ->select(
                'liquidity_logs.id',
                'liquidity_logs.company_id',
                'investor_id',
                'merchant_id',
                'merchants.name as merchant_name',
                'investors.name as investor_name',
                'amount',
                'net_liquidity',
                'description',
                'liquidity_logs.created_at',
                'creator_id',
            )
            // The advance itself, not its user row: liquidity_logs.merchant_id is a
            // FK to users.id — which is what "merchants" is aliased to above — while
            // the screen these rows link to is addressed by merchants.id.
            ->addSelect(['merchant_row_id' => Merchant::rowIdFor('liquidity_logs.merchant_id')]);
    }

    /**
     * The same movements collapsed to one row per posting.
     *
     * Two deviations from legacy, both because legacy grouped on `batch_no` alone:
     *
     * A batch number is not unique on its own — an Investment and a Payment posted
     * in the same batch share it — so this groups on `(batch_no, description,
     * creator_id)`, the same key the merchant screen uses. That splits a mixed batch
     * into its two real postings instead of summing them into one mislabelled row.
     *
     * And a batch is not one merchant: every batch in the book spans dozens of them.
     * Legacy selected `merchants.name` anyway and MySQL handed back whichever row it
     * felt like, so the column named an arbitrary advance out of forty-odd. Here the
     * name is only given when the posting really does concern a single merchant, and
     * the count is reported alongside it. Column totals are unchanged throughout.
     */
    public static function merchantLiquidityLog(array $filters): Builder
    {
        return self::liquidityLogBase($filters)
            ->whereNotNull('merchant_id')
            ->groupBy('batch_no', 'description', 'creator_id')
            ->orderByDesc(DB::raw('max(liquidity_logs.id)'))
            ->select(
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN max(merchants.name) END as merchant_name'),
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN max(merchant_id) END as merchant_id'),
                // The advance behind the posting, named on the same condition as the
                // merchant beside it — a posting spanning forty advances has no single
                // one to open. Correlated on max(merchant_id) rather than through a
                // join to merchants: a merchant can hold more than one advance now,
                // and a join would duplicate the log rows this row sums.
                DB::raw('CASE WHEN count(distinct merchant_id) = 1 THEN (SELECT deal.id FROM merchants as deal WHERE deal.user_id = max(liquidity_logs.merchant_id) AND deal.deleted_at IS NULL ORDER BY deal.id DESC LIMIT 1) END as merchant_row_id'),
                DB::raw('count(distinct merchant_id) as merchant_count'),
                DB::raw('sum(amount) as amount'),
                'description',
                DB::raw('max(liquidity_logs.created_at) as created_at'),
                'batch_no',
                'creator_id',
            );
    }

    /** The filters both liquidity-log reports share. */
    private static function liquidityLogBase(array $filters): Builder
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

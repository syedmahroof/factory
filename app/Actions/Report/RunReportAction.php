<?php

namespace App\Actions\Report;

use App\Helpers\Facades\InvestorHelper;
use App\Helpers\Facades\MerchantHelper;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\DB;

/**
 * Runs one of the reports and pages it.
 *
 * Most of the queries still live in MerchantHelper and InvestorHelper — they are
 * large, joined and well understood, and rewriting them while porting the screen
 * would mean changing two things at once. The four that only existed inline in the
 * legacy DataTable controller now live in ReportQueries. What this adds is the part
 * that was missing everywhere: a named, allow-listed set of reports, validated
 * filters, sorting and paging, so the client cannot name an arbitrary method or an
 * arbitrary sort column.
 */
class RunReportAction
{
    /**
     * report key => [
     *   source      [class, method] that returns the filtered builder,
     *   sortable    columns the client may sort on,
     *   countSelect columns to count over, or null to let paginate() do it,
     *   select      columns to select, or null to leave the query's own,
     *   totals      columns to sum across the whole (unpaged) result,
     *   defaultOrder column to page by when the client names no sort.
     * ].
     *
     * defaultOrder is the column paging falls back to. None of the helper queries
     * orders itself — the legacy DataTable always sent an ORDER BY, so it never
     * showed — and an unordered query paged by LIMIT/OFFSET can repeat a row on one
     * page and drop it from the next. Each report is ordered by its own row key.
     *
     * countSelect exists for the two reports that hang a having() on an alias coming
     * out of a joined sub-select. Laravel counts such a query by wrapping it as a
     * derived table, and a derived table needs every column named exactly once — but
     * `select *` across those joins repeats `rtr`, `funded` and friends. So the count
     * runs over the narrowest unique set that still satisfies the HAVING: the row key
     * plus the alias it filters on.
     */
    public const REPORTS = [
        'payment' => [
            [MerchantHelper::class, 'MerchantWisePaymentReportQuerry'],
            ['funded_date', 'funded', 'rtr', 'balance', 'total_payment', 'net_amount', 'profit'],
            ['merchants.user_id', 'merchant_investors.total_payment'],
            null,
            [],
            'merchants.user_id',
        ],
        'payment_details' => [
            [MerchantHelper::class, 'PaymentDetailsReportQuerry'],
            ['date', 'amount'],
            null, null, [],
            'merchant_payments.id',
        ],
        'investment' => [
            [MerchantHelper::class, 'InvestmentReportQuerry'],
            ['funded_date', 'funded', 'funded_amount', 'invested', 'rtr'],
            ['merchants.user_id', 'merchant_investors.funded_amount'],
            null,
            [],
            'merchants.user_id',
        ],
        'investor_default_rate' => [
            [MerchantHelper::class, 'InvestorDefaultRateReportQuerry'],
            [], null, null, [],
            'merchant_investors.id',
        ],
        'merchant_default_rate' => [
            [MerchantHelper::class, 'MerchantDefaultRateReportQuerry'],
            ['funded_date', 'funded'],
            null, null, [],
            'merchant_investors.id',
        ],
        'overpayment' => [
            [MerchantHelper::class, 'MerchantWiseOverPaymentReportQuerry'],
            ['funded_date', 'funded'],
            null, null, [],
            'merchants.user_id',
        ],
        'liquidity' => [
            [InvestorHelper::class, 'InvestorWiseLiqudityReportQuerry'],
            ['name', 'liquidity', 'net_amount', 'invested', 'transaction'],
            null,
            self::LIQUIDITY_SELECT,
            ['liquidity', 'rtr_balance', 'net_amount', 'transaction', 'invested', 'current_liquidity', 'liquidity_diff'],
            'users.id',
        ],
        'merchant_status_log' => [
            [ReportQueries::class, 'merchantStatusLog'],
            ['created_at', 'merchant_name'],
            null, null, [],
            null,
        ],
        'investor_liquidity_log' => [
            [ReportQueries::class, 'investorLiquidityLog'],
            ['created_at', 'amount'],
            null, null,
            ['amount'],
            null,
        ],
        'merchant_liquidity_log' => [
            [ReportQueries::class, 'merchantLiquidityLog'],
            ['created_at', 'amount'],
            null, null,
            ['amount'],
            null,
        ],
    ];

    /**
     * The liquidity report's derived columns.
     *
     * `liquidity` is the wallet balance the jobs maintain; `current_liquidity` is
     * the same figure recomputed from the underlying rows. `liquidity_diff` is the
     * gap between them, which is the only reason this report exists — a non-zero
     * value means a roll-up has drifted from its source.
     */
    private const LIQUIDITY_SELECT = [
        'users.id',
        'name',
        'liquidity',
        'users.company_id',
        'net_amount',
        'invested',
        'transaction',
        'IFNULL(rtr,0)-IFNULL(paid_amount,0) as rtr_balance',
        'IFNULL(transaction,0)+IFNULL(net_amount,0)-IFNULL(invested,0) as current_liquidity',
        'IFNULL(transaction,0)+IFNULL(net_amount,0)-IFNULL(invested,0)-IFNULL(liquidity,0) as liquidity_diff',
    ];

    public function handle(string $report, array $filters, int $perPage = 25, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        [[$class, $method], $sortable, $countSelect, $select, , $defaultOrder] = self::REPORTS[$report];

        // The helpers read their filters straight off a Request object; ReportQueries
        // takes the array. Both are given the same validated set.
        $query = $class === ReportQueries::class
            ? $class::$method($filters)
            : $class::$method(new Request($filters));

        if ($select) {
            // Three of these are expressions, so the whole list goes through raw.
            $query->select(array_map(fn ($c) => DB::raw($c), $select));
        }

        // Allow-listed. Laravel backticks an identifier so this is not an injection
        // route, but an arbitrary column is still not the client's to choose.
        // Without a sort we leave the query's own ordering alone.
        if ($sort && in_array($sort, $sortable, true)) {
            $query->reorder()->orderBy($sort, $direction === 'asc' ? 'asc' : 'desc');
        } elseif ($defaultOrder) {
            $query->orderByDesc($defaultOrder);
        }

        if (! $countSelect) {
            return $query->paginate($perPage);
        }

        // Counted and paged by hand — see the note on REPORTS for why this one query
        // shape cannot go through paginate().
        $page = Paginator::resolveCurrentPage();
        $total = (clone $query)->select($countSelect)->toBase()->getCountForPagination();

        return new LengthAwarePaginator(
            $total ? $query->forPage($page, $perPage)->get() : collect(),
            $total,
            $perPage,
            $page,
            ['path' => Paginator::resolveCurrentPath()],
        );
    }

    /**
     * Column totals across the whole result, not just the visible page — the footer
     * of the legacy tables summed every row the filter matched.
     */
    public function totals(string $report, array $filters): array
    {
        [[$class, $method], , , $select, $columns] = self::REPORTS[$report];

        if (! $columns) {
            return [];
        }

        $query = $class === ReportQueries::class
            ? $class::$method($filters)
            : $class::$method(new Request($filters));

        if ($select) {
            $query->select(array_map(fn ($c) => DB::raw($c), $select));
        }

        // Summed in PHP rather than with SUM(): these queries group and derive, so
        // the figures only exist once the rows have been built.
        $rows = $query->reorder()->get();

        return collect($columns)
            ->mapWithKeys(fn ($c) => [$c => round((float) $rows->sum(fn ($r) => (float) ($r[$c] ?? 0)), 2)])
            ->all();
    }

    /**
     * What each report is, and what the client should offer for it.
     *
     * `filters` is the set the legacy screen showed — they differ per report, and a
     * screen that offers a filter the query ignores is worse than one that hides it.
     * `date_columns` is what the report's date range can apply to; the helpers
     * switch on that string, so the choices are per report too. `detail` names the
     * parent key of the report's drill-down, when it has one.
     */
    public static function available(): array
    {
        // The five reports with their own action, controller and screen are not
        // here — Payment, Payment Details, Investment, Investor Default Rate and
        // Merchant Default Rate each have their own route under /reports. Their
        // entries in REPORTS stay because the drill-down map is keyed off them.
        $reports = [
            'overpayment' => ['Overpayment', ['lender', 'merchants', 'statuses'], []],
            'liquidity' => ['Liquidity', ['to_date', 'companies', 'investors', 'user_status'], []],
            'investor_liquidity_log' => ['Liquidity Log (Investor)', ['dates', 'descriptions', 'companies', 'investors', 'merchants'], []],
            'merchant_liquidity_log' => ['Liquidity Log (Merchant)', ['dates', 'descriptions', 'companies', 'investors', 'merchants'], []],
            'merchant_status_log' => ['Merchant Status Log', ['dates', 'merchants', 'statuses'], []],
        ];

        return collect($reports)->map(fn ($spec, $id) => [
            'id' => $id,
            'name' => $spec[0],
            'filters' => $spec[1],
            'date_columns' => collect($spec[2])->map(fn ($n, $v) => ['id' => $v, 'name' => $n])->values(),
            'sortable' => self::REPORTS[$id][1],
            'totals' => self::REPORTS[$id][4],
            'detail' => RunReportDetailAction::PARENTS[$id] ?? null,
            'detail_source' => RunReportDetailAction::SOURCES[$id] ?? null,
        ])->values()->all();
    }
}

<?php

namespace App\Actions\Report;

use App\Models\Merchant;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * The Profitability report: what each investor earned, against what they were
 * billed and what they lost, and how the remainder is split with the house.
 *
 * Net profit is `profit − bills − default`, and it is the figure the split columns
 * divide — not the gross profit. An investor who earned well but was billed more
 * has nothing to split, and the report says so.
 *
 * The split columns are the house's share agreements: Equity is a straight 50/50,
 * the others carve out a slice for the introducing agency. They are percentages
 * applied to the same net figure, so the report is one query with a presentation
 * choice on top.
 */
class ProfitabilityReportAction
{
    public const SPLITS = [
        'Equity' => [['title' => '50% Velocity', 'percentage' => 50], ['title' => '50% To Investor', 'percentage' => 50]],
        '50/30/20' => [['title' => '50% Velocity', 'percentage' => 50], ['title' => '30% To Investor', 'percentage' => 30], ['title' => '20% Investor Agency', 'percentage' => 20]],
        '65/20/15' => [['title' => '65% Velocity', 'percentage' => 65], ['title' => '20% To Investor', 'percentage' => 20], ['title' => '15% Investor Agency', 'percentage' => 15]],
    ];

    public const SORTABLE = [
        'name' => 'users.name',
        'ctd' => 'ctd',
        'profit' => 'profit',
        'bill_amount' => 'bill_amount',
        'default_amount' => 'default_amount',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = ['ctd', 'profit', 'bill_amount', 'default_amount', 'net_profit'];

    /** What the page length menu offers; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, unordered and unpaged.
     *
     * Legacy dropped any investor with neither collections nor bills in the period
     * — a row of zeroes says nothing — and that filter is a HAVING here so paging
     * counts the same rows the screen shows.
     */
    public function query(array $filters): Builder
    {
        $fromDate = $filters['from_date'] ?: date('Y-m-d', strtotime('-1 year'));
        $toDate = $filters['to_date'] ?: date('Y-m-d');

        // Which advances count as lost. Read off the moment the advance was last
        // moved, not when it was funded — one that defaulted this month counts
        // against this month however old it is.
        $defaulted = Merchant::query()
            ->whereIn('status_id', [Merchant::Default, Merchant::DefaultOrLegal])
            ->whereDate('last_status_updated_date', '>=', $fromDate)
            ->whereDate('last_status_updated_date', '<=', $toDate)
            ->pluck('user_id')
            ->all();

        // Dates come from date(), ids from sqlIdList() — the only values that reach
        // these fragments, and neither can carry SQL.
        $dates = " AND `date` >= '{$fromDate}' AND `date` <= '{$toDate}' ";
        $defaultedFilter = $defaulted ? ' AND merchant_id IN ('.sqlIdList($defaulted).')' : '';
        $categories = ($filters['bill_categories'] ?? [])
            ? ' AND category_id IN ('.sqlIdList($filters['bill_categories']).')'
            : '';

        return User::query()
            ->where('user_type_id', UserType::Investor)
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('users.id', $v))
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('users.name', 'LIKE', '%'.$v.'%'))
            ->leftJoin(DB::raw("(SELECT SUM(amount-management_fee) as ctd, SUM(profit) as profit, merchant_payment_investors.investor_id FROM merchant_payment_investors WHERE id > 0 {$dates} GROUP BY merchant_payment_investors.investor_id) as payments"), 'payments.investor_id', 'users.id')
            ->leftJoin(DB::raw("(SELECT SUM(abs(amount)) as bill_amount, investor_transactions.user_id FROM investor_transactions WHERE id > 0 {$dates} {$categories} AND deleted_at IS NULL GROUP BY investor_transactions.user_id) as bills"), 'bills.user_id', 'users.id')
            ->leftJoin(DB::raw("(SELECT SUM(invested-paid_net_amount) AS default_amount, investor_id FROM merchant_investors WHERE id > 0 {$defaultedFilter} AND deleted_at IS NULL GROUP BY investor_id) as ctd_default_merchant"), 'ctd_default_merchant.investor_id', 'users.id')
            ->havingRaw('IFNULL(ctd,0) <> 0 OR IFNULL(bill_amount,0) <> 0')
            ->select(
                'users.id',
                'users.name',
                'payments.ctd',
                'payments.profit',
                'bills.bill_amount',
                'ctd_default_merchant.default_amount',
            );
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $column = self::SORTABLE[$sort] ?? 'users.name';
        $splits = self::SPLITS[$filters['type'] ?? 'Equity'] ?? self::SPLITS['Equity'];

        return $this->query($filters)
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->through(fn ($row) => $this->present($row, $splits));
    }

    /**
     * Column totals over every investor the filter matched, with the splits taken
     * on the total net rather than summed from the rounded per-row figures.
     */
    public function totals(array $filters): array
    {
        $splits = self::SPLITS[$filters['type'] ?? 'Equity'] ?? self::SPLITS['Equity'];

        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'profitability_report')
            ->selectRaw('SUM(ctd) as ctd, SUM(profit) as profit, SUM(bill_amount) as bill_amount, SUM(default_amount) as default_amount')
            ->first();

        $totals = collect(['ctd', 'profit', 'bill_amount', 'default_amount'])
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();

        $totals['net_profit'] = round($totals['profit'] - $totals['bill_amount'] - $totals['default_amount'], 2);
        $totals['splits'] = array_map(
            fn ($s) => round($totals['net_profit'] * $s['percentage'] / 100, 2),
            $splits,
        );

        return $totals;
    }

    /** The split columns the chosen agreement produces. */
    public function columns(?string $type): array
    {
        return self::SPLITS[$type ?? 'Equity'] ?? self::SPLITS['Equity'];
    }

    private function present(object $row, array $splits): object
    {
        foreach (['ctd', 'profit', 'bill_amount', 'default_amount'] as $column) {
            $row->$column = round((float) $row->$column, 2);
        }

        // What is actually left to divide: earnings less what the investor was
        // billed and less what they lost to defaults.
        $row->net_profit = round($row->profit - $row->bill_amount - $row->default_amount, 2);

        // Legacy showed a Pref Return column that was always a dash. Kept as a
        // column the screen renders, not a figure anything computes.
        $row->splits = array_map(fn ($s) => round($row->net_profit * $s['percentage'] / 100, 2), $splits);

        return $row;
    }
}

<?php

namespace App\Actions\Report;

use App\Helpers\Facades\InvestorHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * The Liqudity report: every investor's wallet, recomputed beside the figure the
 * jobs maintain.
 *
 * `liquidity` is what users.liquidity holds; `current_liquidity` is the same sum
 * rebuilt from transactions, payments and participations. `liquidity_diff` is the
 * gap between them, and a non-zero value means a roll-up has drifted from its
 * source — which is the only reason the report exists. Legacy shipped that column
 * hidden, so it is in the chooser rather than on the grid.
 *
 * Spelled "Liqudity" throughout, as the legacy route and menu spell it.
 */
class LiquidityReportAction
{
    public const SORTABLE = [
        'id' => 'users.id',
        'company_name' => 'company_name',
        'name' => 'users.name',
        'liquidity' => 'users.liquidity',
        'rtr_balance' => 'rtr_balance',
        'net_amount' => 'net_amount',
        'transaction' => 'transaction',
        'invested' => 'invested',
        'current_liquidity' => 'current_liquidity',
        'liquidity_diff' => 'liquidity_diff',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = ['liquidity', 'rtr_balance', 'net_amount', 'transaction', 'invested', 'current_liquidity', 'liquidity_diff'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    public function query(array $filters): Builder
    {
        // The company name comes from a correlated sub-select rather than a join to
        // users-as-companies: the helper's own WHERE names `user_type_id` and
        // `liquidity` unqualified, and a second users table in scope makes both
        // ambiguous. The sub-select adds no table to resolve against.
        return InvestorHelper::InvestorWiseLiqudityReportQuerry(new Request($filters))
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('users.name', 'LIKE', '%'.$v.'%'))
            ->select(
                'users.id',
                'users.name',
                DB::raw('(SELECT name FROM users AS company WHERE company.id = users.company_id) as company_name'),
                'users.liquidity',
                DB::raw('IFNULL(rtr,0)-IFNULL(paid_amount,0) as rtr_balance'),
                'net_amount',
                'transaction',
                'invested',
                DB::raw('IFNULL(transaction,0)+IFNULL(net_amount,0)-IFNULL(invested,0) as current_liquidity'),
                DB::raw('IFNULL(transaction,0)+IFNULL(net_amount,0)-IFNULL(invested,0)-IFNULL(users.liquidity,0) as liquidity_diff'),
            );
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'desc'): LengthAwarePaginator
    {
        $column = self::SORTABLE[$sort] ?? 'users.name';

        return $this->query($filters)
            ->reorder()
            ->orderBy($column, $direction === 'asc' ? 'asc' : 'desc')
            ->paginate($perPage)
            ->through(function ($row) {
                foreach (self::TOTALS as $c) {
                    $row->$c = (float) $row->$c;
                }

                return $row;
            });
    }

    public function totals(array $filters): array
    {
        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'liquidity_report')
            ->selectRaw(implode(',', array_map(fn ($c) => "SUM($c) as $c", self::TOTALS)))
            ->first();

        return collect(self::TOTALS)
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();
    }
}

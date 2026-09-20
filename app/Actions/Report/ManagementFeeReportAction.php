<?php

namespace App\Actions\Report;

use App\Models\Merchant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * The Management Fee report: what each advance was charged up front, against what
 * has actually been collected on it since.
 *
 * The four named fees are the ones taken at funding, out of merchant_investor_fees
 * — a pivot of that table, one column per fee name. The last two are what the
 * payment split has taken since, summed off merchant_payments.
 */
class ManagementFeeReportAction
{
    /** The four fees a participation can be charged, as merchant_investor_fees names them. */
    private const FEES = [
        'Commission' => 'Commission',
        'Syndication Fee' => 'SyndicationFee',
        // 'Underwriting Fee' => 'UnderwritingFee',
        'Up Sell Commission' => 'UpSellCommission',
    ];

    public const SORTABLE = [
        'merchant_id' => 'merchants.user_id',
        'merchant_name' => 'merchant_users.name',
        'Commission' => 'Commission',
        'SyndicationFee' => 'SyndicationFee',
        // 'UnderwritingFee' => 'UnderwritingFee',
        'UpSellCommission' => 'UpSellCommission',
        'investors_management_fee' => 'investors_management_fee',
        'investors_agent_fee' => 'investors_agent_fee',
        'balance' => 'merchants.balance',
    ];

    /** The columns the footer sums, across the whole filtered set. */
    public const TOTALS = [
        'Commission',
        'SyndicationFee',
        // 'UnderwritingFee',
        'UpSellCommission',
        'investors_management_fee',
        'investors_agent_fee',
    ];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, unordered and unpaged.
     *
     * Both sub-selects are literal — the fee names are this class's own constants
     * and no request value reaches either fragment.
     */
    public function query(array $filters): Builder
    {
        $fees = 'merchant_id,'.collect(self::FEES)
            ->map(fn ($alias, $name) => "sum(CASE WHEN name = \"{$name}\" THEN amount END) {$alias}")
            ->implode(',');

        return Merchant::query()
            ->join('users as merchant_users', 'merchant_users.id', 'merchants.user_id')
            ->when($filters['merchant_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchants.user_id', $v))
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('merchant_users.name', 'LIKE', '%'.$v.'%'))
            ->leftJoin(DB::raw("(SELECT {$fees} FROM merchant_investor_fees WHERE id > 0 AND deleted_at IS NULL GROUP BY merchant_investor_fees.merchant_id) as merchant_investor_fees"), 'merchant_investor_fees.merchant_id', 'merchants.user_id')
            ->leftJoin(DB::raw('(SELECT merchant_id, sum(investors_management_fee) as investors_management_fee, sum(investors_agent_fee) as investors_agent_fee FROM merchant_payments WHERE id > 0 AND deleted_at IS NULL GROUP BY merchant_payments.merchant_id) as merchant_payments'), 'merchant_payments.merchant_id', 'merchants.user_id')
            ->select(
                'merchants.user_id as merchant_id',
                // The advance's own id, which admin.merchants.show binds — the
                // merchant_id beside it is the merchant's users.id.
                'merchants.id as merchant_row_id',
                'merchant_users.name as merchant_name',
                'merchants.balance',
                'Commission',
                'SyndicationFee',
                // 'UnderwritingFee',
                'UpSellCommission',
                'investors_management_fee',
                'investors_agent_fee',
            );
    }

    public function paginate(array $filters, int $perPage = 15, ?string $sort = null, string $direction = 'asc'): LengthAwarePaginator
    {
        // Legacy opened on the outstanding balance ascending, so the advances with
        // the least left to collect came first.
        $column = self::SORTABLE[$sort] ?? 'merchants.balance';

        return $this->query($filters)
            ->reorder()
            ->orderBy($column, $direction === 'desc' ? 'desc' : 'asc')
            ->paginate($perPage)
            ->through(function ($row) {
                foreach (self::TOTALS as $c) {
                    $row->$c = (float) $row->$c;
                }

                $row->balance = (float) $row->balance;

                return $row;
            });
    }

    public function totals(array $filters): array
    {
        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'management_fee_report')
            ->selectRaw(implode(',', array_map(fn ($c) => "SUM($c) as $c", self::TOTALS)))
            ->first();

        return collect(self::TOTALS)
            ->mapWithKeys(fn ($c) => [$c => round((float) ($row->$c ?? 0), 2)])
            ->all();
    }
}

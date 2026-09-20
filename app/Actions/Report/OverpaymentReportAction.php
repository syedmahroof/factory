<?php

namespace App\Actions\Report;

use App\Helpers\Facades\MerchantHelper;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

/**
 * The Overpayment report: advances that have collected more than their investors
 * are owed.
 *
 * A short report and an exception list rather than a summary — every row on it is
 * money to be given back or reallocated, so an empty result is the good outcome.
 */
class OverpaymentReportAction
{
    public const SORTABLE = [
        'merchant_id' => 'merchants.user_id',
        'merchant_name' => 'users.name',
        'overpayment' => 'overpayment',
    ];

    /** The column the footer sums, across the whole filtered set. */
    public const TOTALS = ['overpayment'];

    /** What the legacy page length menu offered; the first is the default. */
    public const PER_PAGE_OPTIONS = [15, 50, 100, 250, 1000];

    /**
     * The report, unordered and unpaged.
     *
     * Legacy swapped in a date-aware query as soon as a range was given — the
     * undated one reads the roll-up on merchant_payments, the dated one
     * re-aggregates over the range — and applied the same HAVING to both.
     */
    public function query(array $filters): Builder
    {
        $dated = ($filters['from_date'] ?? '') || ($filters['to_date'] ?? '');

        $query = $dated
            ? MerchantHelper::MerchantWiseOverPaymentReportDateQuerry(new Request($filters))
            : MerchantHelper::MerchantWiseOverPaymentReportQuerry(new Request($filters));

        return $query
            ->whereRaw('total_payment>investor_rtr')
            ->when($filters['search'] ?? '', fn ($q, $v) => $q->where('users.name', 'LIKE', '%'.$v.'%'))
            ->select(
                'merchants.user_id as merchant_id',
                // The advance's own id, which admin.merchants.show binds — the
                // merchant_id beside it is the merchant's users.id.
                'merchants.id as merchant_row_id',
                'users.name as merchant_name',
                DB::raw('total_payment-investor_rtr as overpayment'),
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
                $row->overpayment = (float) $row->overpayment;

                return $row;
            });
    }

    public function totals(array $filters): array
    {
        $row = DB::query()
            ->fromSub($this->query($filters)->toBase(), 'overpayment_report')
            ->selectRaw('SUM(overpayment) as overpayment')
            ->first();

        return ['overpayment' => round((float) ($row->overpayment ?? 0), 2)];
    }
}

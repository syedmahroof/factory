<?php

namespace App\Actions\Report;

use App\Actions\ActionResult;
use App\Actions\Merchant\ListPendingInvestmentsAction;
use App\Models\Merchant;
use App\Models\MerchantPaymentTermDate;
use App\Models\UserType;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/**
 * Everything the Portfolio Activity report draws, for one date range.
 *
 * Read-only: eight aggregate queries, each scoped to the same [from, to]
 * pair and each reading columns that already exist — payments by payment
 * date, one-time fees by the date the fee was written, investor movements
 * by transaction date, advances by funded date. Prior-period deltas are
 * the identical queries shifted back one period length, so "vs prior"
 * always compares like with like.
 *
 * Nothing here recomputes a job-owned roll-up; balances and completion
 * percentages are read as the jobs left them.
 */
class PeriodActivityAction
{
    private const CACHE_SECONDS = 300;

    public function handle(Carbon $from, Carbon $to, string $granularity = 'auto'): ActionResult
    {
        try {
            $key = 'report.activity.v3.'.$from->toDateString().'.'.$to->toDateString().'.'.$granularity;

            $data = Cache::remember($key, self::CACHE_SECONDS, function () use ($from, $to, $granularity) {
                $days = (int) $from->diffInDays($to) + 1;
                $prevTo = $from->copy()->subDay()->endOfDay();
                $prevFrom = $prevTo->copy()->subDays($days - 1)->startOfDay();

                return [
                    'payments' => $this->payments($from, $to),
                    'payments_prev' => $this->payments($prevFrom, $prevTo),
                    'series' => $this->series($from, $to, $days, $granularity),
                    'by_tone' => $this->paymentsByTone($from, $to),
                    'fees' => $this->oneTimeFees($from, $to),
                    'fees_prev_total' => (float) $this->oneTimeFeeQuery($prevFrom, $prevTo)->sum('amount'),
                    'investor_flow' => $this->investorFlow($from, $to),
                    'new_merchants' => $this->newMerchants($from, $to),
                    'status_moves' => $this->statusMoves($from, $to),
                    'ach' => $this->termDates($from, $to),
                    'top_payers' => $this->topPayers($from, $to),
                    'accounts' => $this->accounts(),
                    'liquidity_now' => (float) DB::table('users')->sum('liquidity'),
                    'prev_from' => $prevFrom->toDateString(),
                    'prev_to' => $prevTo->toDateString(),
                ];
            });

            return ActionResult::success($data);
        } catch (\Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to build the activity report.', 500);
        }
    }

    /**
     * @return object{count: int, amount: float, management_fee: float, agent_fee: float}
     */
    private function payments(Carbon $from, Carbon $to): object
    {
        return DB::table('merchant_payments')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COUNT(*) AS count')
            ->selectRaw('COALESCE(SUM(amount), 0) AS amount')
            ->selectRaw('COALESCE(SUM(investors_management_fee), 0) AS management_fee')
            ->selectRaw('COALESCE(SUM(investors_agent_fee), 0) AS agent_fee')
            ->first();
    }

    /**
     * Collections over time. The operator can force a granularity from the
     * chart (day / week / month / year); 'auto' buckets by span so the chart
     * never draws hundreds of bars — daily for up to ~6 weeks, weekly for up
     * to ~7 months, monthly beyond that.
     *
     * @return array{bucket: string, rows: array<int, object{label: string, amount: float}>}
     */
    private function series(Carbon $from, Carbon $to, int $days, string $granularity = 'auto'): array
    {
        $bucket = match ($granularity) {
            'day', 'week', 'month', 'year' => $granularity,
            default => match (true) {
                $days <= 45 => 'day',
                $days <= 220 => 'week',
                default => 'month',
            },
        };

        [$expression, $format] = match ($bucket) {
            'day' => ["DATE_FORMAT(date, '%Y-%m-%d')", 'M j'],
            'week' => ["STR_TO_DATE(CONCAT(YEARWEEK(date, 3), ' Monday'), '%x%v %W')", 'M j'],
            'year' => ["DATE_FORMAT(date, '%Y-01-01')", 'Y'],
            default => ["DATE_FORMAT(date, '%Y-%m-01')", 'M Y'],
        };

        $rows = DB::table('merchant_payments')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$from, $to])
            ->selectRaw("$expression AS bucket_date")
            ->selectRaw('COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('bucket_date')
            ->orderBy('bucket_date')
            ->get()
            ->map(fn ($row) => (object) [
                'label' => Carbon::parse($row->bucket_date)->format($format),
                'amount' => (float) $row->amount,
            ])
            ->all();

        return ['bucket' => $bucket, 'rows' => $rows];
    }

    /**
     * Who the money actually came from: payments in the period grouped into
     * the status tones the Merchant model already defines, laid against the
     * advances currently sitting in each tone — so a tone that paid nothing
     * still appears, carrying the balance it owes.
     *
     * @return array<string, array{payments: int, amount: float, merchants: int, balance: float}>
     */
    private function paymentsByTone(Carbon $from, Carbon $to): array
    {
        $tones = array_fill_keys(['live', 'watch', 'trouble', 'closed', 'muted'], [
            'payments' => 0,
            'amount' => 0.0,
            'merchants' => 0,
            'balance' => 0.0,
        ]);

        $paid = DB::table('merchant_payments as p')
            ->join('merchants as m', 'm.user_id', '=', 'p.merchant_id')
            ->whereNull('p.deleted_at')
            ->whereBetween('p.date', [$from, $to])
            ->selectRaw('m.status_id, COUNT(*) AS count, COALESCE(SUM(p.amount), 0) AS amount')
            ->groupBy('m.status_id')
            ->get();

        foreach ($paid as $row) {
            $tone = Merchant::statusTone($row->status_id);
            $tones[$tone]['payments'] += (int) $row->count;
            $tones[$tone]['amount'] += (float) $row->amount;
        }

        $book = DB::table('merchants')
            ->whereNull('deleted_at')
            ->selectRaw('status_id, COUNT(*) AS count, COALESCE(SUM(balance), 0) AS balance')
            ->groupBy('status_id')
            ->get();

        foreach ($book as $row) {
            $tone = Merchant::statusTone($row->status_id);
            $tones[$tone]['merchants'] += (int) $row->count;
            $tones[$tone]['balance'] += (float) $row->balance;
        }

        return $tones;
    }

    private function oneTimeFeeQuery(Carbon $from, Carbon $to)
    {
        return DB::table('merchant_investor_fees')
            ->whereNull('deleted_at')
            ->whereBetween('created_at', [$from, $to]);
    }

    /**
     * One-time fees by name, read from the data rather than a hard-coded
     * column list, so every fee type the desk writes appears automatically.
     *
     * @return array<int, object{name: string, count: int, amount: float}>
     */
    private function oneTimeFees(Carbon $from, Carbon $to): array
    {
        return $this->oneTimeFeeQuery($from, $to)
            ->selectRaw('name, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('name')
            ->orderByDesc(DB::raw('SUM(amount)'))
            ->get()
            ->all();
    }

    /**
     * @return object{count: int, credited: float, debited: float}
     */
    private function investorFlow(Carbon $from, Carbon $to): object
    {
        return DB::table('investor_transactions')
            ->whereNull('deleted_at')
            ->whereBetween('date', [$from, $to])
            ->selectRaw('COUNT(*) AS count')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount > 0 THEN amount END), 0) AS credited')
            ->selectRaw('COALESCE(SUM(CASE WHEN amount < 0 THEN amount END), 0) AS debited')
            ->first();
    }

    /**
     * @return object{count: int, funded: float, avg_factor: float}
     */
    private function newMerchants(Carbon $from, Carbon $to): object
    {
        return DB::table('merchants')
            ->whereNull('deleted_at')
            ->whereBetween('funded_date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('COUNT(*) AS count')
            ->selectRaw('COALESCE(SUM(funded), 0) AS funded')
            ->selectRaw('COALESCE(AVG(factor_rate), 0) AS avg_factor')
            ->first();
    }

    /**
     * @return array<int, object{merchant: string, merchant_id: int, old_status_id: int, new_status_id: int, date: string}>
     */
    private function statusMoves(Carbon $from, Carbon $to): array
    {
        return DB::table('merchant_status_logs as l')
            ->join('users as u', 'u.id', '=', 'l.merchant_id')
            ->whereBetween('l.created_at', [$from, $to])
            ->orderByDesc('l.created_at')
            ->limit(10)
            ->select(
                'u.name as merchant',
                'l.merchant_id',
                'l.old_status_id',
                'l.new_status_id',
                'l.created_at as date',
            )
            // merchants.id, which admin.merchants.show binds — merchant_id is the
            // merchant's users.id, and the two are different numbers.
            ->addSelect(['merchant_row_id' => Merchant::rowIdFor('l.merchant_id')])
            ->get()
            ->all();
    }

    /**
     * ACH schedule performance: every term date falling in the period, by
     * outcome. merchant_payment_term_dates has no deleted_at column.
     *
     * @return array{total: int, total_amount: float, rows: array<int, array{status_id: int, count: int, amount: float}>}
     */
    private function termDates(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('merchant_payment_term_dates')
            ->whereBetween('date', [$from->toDateString(), $to->toDateString()])
            ->selectRaw('status_id, COUNT(*) AS count, COALESCE(SUM(amount), 0) AS amount')
            ->groupBy('status_id')
            ->get()
            ->map(fn ($row) => [
                'status_id' => (int) $row->status_id,
                'count' => (int) $row->count,
                'amount' => (float) $row->amount,
            ])
            ->all();

        return [
            'total' => array_sum(array_column($rows, 'count')),
            'total_amount' => array_sum(array_column($rows, 'amount')),
            'paid' => collect($rows)->firstWhere('status_id', MerchantPaymentTermDate::Paid)['count'] ?? 0,
            'rows' => $rows,
        ];
    }

    /**
     * The book's account counts, right now — deliberately not period-scoped.
     * "Active investors" means holding an active syndication, which is what
     * the old dashboard block claimed to show but couldn't (it repeated the
     * unfiltered total).
     *
     * @return array<string, int>
     */
    private function accounts(): array
    {
        $byStatus = DB::table('merchants')
            ->whereNull('deleted_at')
            ->selectRaw('status_id, COUNT(*) AS count')
            ->selectRaw('COALESCE(SUM(funded), 0) AS funded')
            ->selectRaw('COALESCE(SUM(balance), 0) AS balance')
            ->groupBy('status_id')
            ->get()
            ->keyBy('status_id');

        $count = fn (array $ids): int => array_sum(array_map(fn ($id) => (int) ($byStatus[$id]->count ?? 0), $ids));
        $sum = fn (array $ids, string $col): float => array_sum(array_map(fn ($id) => (float) ($byStatus[$id]->{$col} ?? 0), $ids));

        $defaultLegal = [Merchant::Default, Merchant::ReferredToLegal, Merchant::DefaultPlus, Merchant::DefaultOrLegal];

        $active = DB::table('merchant_investors')
            ->whereNull('deleted_at')
            ->where('active_status', 'Active')
            ->selectRaw('COUNT(DISTINCT investor_id) AS investors, COALESCE(SUM(invested), 0) AS invested')
            ->first();

        $pending = DB::table('merchant_investors')
            ->whereNull('deleted_at')
            ->where('active_status', ListPendingInvestmentsAction::STATUS)
            ->selectRaw('COUNT(*) AS count, COALESCE(SUM(funded), 0) AS amount')
            ->first();

        return [
            'merchants' => ['count' => (int) $byStatus->sum('count'), 'amount' => (float) $byStatus->sum('funded')],
            'investors' => [
                'count' => (int) DB::table('users')->where('user_type_id', UserType::Investor)->count(),
                'amount' => (float) DB::table('users')->where('user_type_id', UserType::Investor)->sum('liquidity'),
            ],
            'active_merchants' => ['count' => $count([Merchant::ActiveAdvance]), 'amount' => $sum([Merchant::ActiveAdvance], 'balance')],
            'active_investors' => ['count' => (int) $active->investors, 'amount' => (float) $active->invested],
            'completed' => ['count' => $count([Merchant::AdvanceCompleted]), 'amount' => $sum([Merchant::AdvanceCompleted], 'funded')],
            'collections' => ['count' => $count([Merchant::Collections]), 'amount' => $sum([Merchant::Collections], 'balance')],
            'default_legal' => ['count' => $count($defaultLegal), 'amount' => $sum($defaultLegal, 'balance')],
            'pending' => ['count' => (int) $pending->count, 'amount' => (float) $pending->amount],
        ];
    }

    /**
     * @return array<int, object{merchant: string, merchant_id: int, status_id: int, completed_percentage: float, count: int, amount: float, fees: float}>
     */
    private function topPayers(Carbon $from, Carbon $to): array
    {
        return DB::table('merchant_payments as p')
            ->join('merchants as m', 'm.user_id', '=', 'p.merchant_id')
            ->join('users as u', 'u.id', '=', 'p.merchant_id')
            ->whereNull('p.deleted_at')
            ->whereBetween('p.date', [$from, $to])
            // m.id is what admin.merchants.show binds; m.user_id is the merchant's
            // users.id, which is what the payments are keyed on.
            ->selectRaw('u.name AS merchant, m.user_id AS merchant_id, m.id AS merchant_row_id, m.status_id, m.completed_percentage')
            ->selectRaw('COUNT(*) AS count, COALESCE(SUM(p.amount), 0) AS amount')
            ->selectRaw('COALESCE(SUM(p.investors_management_fee + p.investors_agent_fee), 0) AS fees')
            ->groupBy('u.name', 'm.user_id', 'm.id', 'm.status_id', 'm.completed_percentage')
            ->orderByDesc(DB::raw('SUM(p.amount)'))
            ->limit(8)
            ->get()
            ->all();
    }
}

<?php

namespace App\Actions\Dashboard;

use App\Models\Customer;
use App\Models\Item;
use App\Models\MaintenanceOrder;
use App\Models\Ncr;
use App\Models\ProductionOrder;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\WorkCenter;
use Carbon\Carbon;

/**
 * The whole factory desk for one date range.
 *
 * One action rather than a controller full of queries, because every panel on the
 * dashboard has to agree about what "this period" means — the output series, the
 * order mixes and the headline figures are all cut from the same [from, to] that
 * ResolveReportPeriodAction hands back, and the prior window is that same span
 * ending the day before it.
 *
 * Two things are deliberately *not* period-scoped, and say so on the screen: the
 * stock position and the open-work counts. Those are the state of the factory now;
 * asking what the warehouse held during a range that ended in March is a different
 * question and a different report.
 */
class BuildFactoryActivityAction
{
    /**
     * Where each series takes its date from. Production is dated by when the order
     * actually finished, not when it was raised — an order opened in June and
     * closed in August is August's output.
     */
    private const PRODUCTION_DATE = 'actual_end_date';

    /** Coarsest last: the order the chart steps through when a range is too long. */
    private const BUCKETS = ['day', 'week', 'month', 'year'];

    /** More columns than this and the chart stops being readable. */
    private const MAX_BUCKETS = 400;

    public function __construct(private ResolveReportPeriodAction $period) {}

    public function execute(
        string $period = '30d',
        string $granularity = 'auto',
        ?string $fromDate = null,
        ?string $toDate = null,
    ): array {
        [$from, $to] = $this->period->handle($period, $fromDate, $toDate);

        // The prior window is the same number of days, ending the day before this
        // one opens — so "last 30 days" always compares against the 30 before it.
        $span = $from->diffInDays($to) + 1;
        $prevTo = $from->copy()->subDay()->endOfDay();
        $prevFrom = $prevTo->copy()->subDays($span - 1)->startOfDay();

        $bucket = $this->bucket($granularity, $span);

        return [
            'from' => $from->toDateString(),
            'to' => $to->toDateString(),
            'prev_from' => $prevFrom->toDateString(),
            'prev_to' => $prevTo->toDateString(),
            'presets' => ResolveReportPeriodAction::presets(),
            'granularities' => ['day' => 'Daily', 'week' => 'Weekly', 'month' => 'Monthly', 'year' => 'Yearly'],

            'production' => $this->production($from, $to),
            'production_prev' => $this->production($prevFrom, $prevTo),
            'purchasing' => $this->purchasing($from, $to),
            'purchasing_prev' => $this->purchasing($prevFrom, $prevTo),
            'sales' => $this->sales($from, $to),
            'sales_prev' => $this->sales($prevFrom, $prevTo),
            'quality' => $this->quality($from, $to),
            'quality_prev' => $this->quality($prevFrom, $prevTo),

            'series' => $this->series($from, $to, $bucket),
            'production_by_status' => $this->productionByStatus($from, $to),
            'purchasing_by_status' => $this->orderMix(PurchaseOrder::query(), $from, $to),
            'sales_by_status' => $this->orderMix(SalesOrder::query(), $from, $to),
            'movements' => $this->movements($from, $to),
            'top_items' => $this->topItems($from, $to),

            // Right now, not period-scoped.
            'stock' => $this->stock(),
            'open_work' => $this->openWork(),
            'low_stock' => $this->lowStock(),
        ];
    }

    /* ── period figures ──────────────────────────────────────────────────── */

    private function production(Carbon $from, Carbon $to): array
    {
        $row = ProductionOrder::query()
            ->whereBetween(self::PRODUCTION_DATE, [$from, $to])
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(actual_quantity), 0) AS quantity, COALESCE(SUM(scrap_quantity), 0) AS scrap, COALESCE(SUM(actual_cost), 0) AS cost')
            ->first();

        $quantity = (float) $row->quantity;
        $scrap = (float) $row->scrap;
        $made = $quantity + $scrap;

        return [
            'orders' => (int) $row->orders,
            'quantity' => $quantity,
            'scrap' => $scrap,
            'cost' => (float) $row->cost,
            // Yield is only a number when something was actually run.
            'yield' => $made > 0 ? ($quantity / $made) * 100 : null,
        ];
    }

    private function purchasing(Carbon $from, Carbon $to): array
    {
        $row = PurchaseOrder::query()
            ->whereBetween('order_date', [$from, $to])
            ->whereNot('status', 'cancelled')
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS amount')
            ->first();

        return ['orders' => (int) $row->orders, 'amount' => (float) $row->amount];
    }

    private function sales(Carbon $from, Carbon $to): array
    {
        $row = SalesOrder::query()
            ->whereBetween('order_date', [$from, $to])
            ->whereNot('status', 'cancelled')
            ->selectRaw('COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS amount')
            ->first();

        $shipped = SalesOrder::query()
            ->whereBetween('order_date', [$from, $to])
            ->whereIn('status', ['shipped', 'delivered', 'invoiced', 'closed'])
            ->count();

        return ['orders' => (int) $row->orders, 'amount' => (float) $row->amount, 'shipped' => $shipped];
    }

    private function quality(Carbon $from, Carbon $to): array
    {
        $raised = Ncr::query()->whereBetween('reported_date', [$from, $to])->count();
        $closed = Ncr::query()->whereBetween('actual_close_date', [$from, $to])->count();

        return ['raised' => $raised, 'closed' => $closed];
    }

    /* ── the output series ───────────────────────────────────────────────── */

    /**
     * `auto` picks the bucket that leaves a readable number of bars: a fortnight
     * is worth drawing day by day, five years is not.
     */
    private function bucket(string $granularity, int $span): string
    {
        $chosen = in_array($granularity, self::BUCKETS, true)
            ? $granularity
            : match (true) {
                $span <= 31 => 'day',
                $span <= 120 => 'week',
                $span <= 1100 => 'month',
                default => 'year',
            };

        /*
         * A range too long for the bucket that was asked for is drawn coarser
         * rather than cut off at an arbitrary bar — five years of daily columns
         * is not a chart, and truncating it would label the first sixteen months
         * as the whole period.
         */
        $days = ['day' => 1, 'week' => 7, 'month' => 28, 'year' => 365];

        foreach (array_slice(self::BUCKETS, array_search($chosen, self::BUCKETS, true)) as $step) {
            if (intdiv($span, $days[$step]) <= self::MAX_BUCKETS) {
                return $step;
            }
        }

        return 'year';
    }

    /**
     * Output per bucket, gaps included. A week with no production is a bar of
     * height zero, not a missing column — the shape of the run matters.
     */
    private function series(Carbon $from, Carbon $to, string $bucket): array
    {
        $column = self::PRODUCTION_DATE;

        // Every bucket is named by the date it starts on, so the label is a date
        // format rather than a week number nobody reads off a chart.
        $startExpr = match ($bucket) {
            'week' => "DATE_SUB(DATE({$column}), INTERVAL WEEKDAY({$column}) DAY)",
            'month' => "DATE_FORMAT({$column}, '%Y-%m-01')",
            'year' => "DATE_FORMAT({$column}, '%Y-01-01')",
            default => "DATE({$column})",
        };

        $rows = ProductionOrder::query()
            ->whereBetween($column, [$from, $to])
            ->selectRaw("{$startExpr} AS bucket_start, COALESCE(SUM(actual_quantity), 0) AS quantity, COUNT(*) AS orders")
            ->groupBy('bucket_start')
            ->get()
            ->keyBy(fn ($r) => Carbon::parse($r->bucket_start)->toDateString());

        $out = [];

        foreach ($this->buckets($from, $to, $bucket) as $start) {
            $key = $start->toDateString();
            $row = $rows->get($key);

            $out[] = [
                'key' => $key,
                'label' => $this->label($start, $bucket),
                'quantity' => (float) ($row->quantity ?? 0),
                'orders' => (int) ($row->orders ?? 0),
            ];
        }

        return ['bucket' => $bucket, 'rows' => $out];
    }

    /** @return list<Carbon> */
    private function buckets(Carbon $from, Carbon $to, string $bucket): array
    {
        $cursor = match ($bucket) {
            'week' => $from->copy()->startOfWeek(),
            'month' => $from->copy()->startOfMonth(),
            'year' => $from->copy()->startOfYear(),
            default => $from->copy()->startOfDay(),
        };

        $out = [];

        // Guard the loop as well as the condition: a bad range should give a short
        // chart, never a request that never returns.
        while ($cursor->lte($to) && count($out) <= self::MAX_BUCKETS) {
            $out[] = $cursor->copy();

            $cursor = match ($bucket) {
                'week' => $cursor->addWeek(),
                'month' => $cursor->addMonthNoOverflow(),
                'year' => $cursor->addYear(),
                default => $cursor->addDay(),
            };
        }

        return $out;
    }

    private function label(Carbon $start, string $bucket): string
    {
        return match ($bucket) {
            'week' => $start->format('j M'),
            'month' => $start->format('M Y'),
            'year' => $start->format('Y'),
            default => $start->format('j M'),
        };
    }

    /* ── mixes ───────────────────────────────────────────────────────────── */

    private function productionByStatus(Carbon $from, Carbon $to): array
    {
        return ProductionOrder::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('status, COUNT(*) AS orders, COALESCE(SUM(planned_quantity), 0) AS planned, COALESCE(SUM(actual_quantity), 0) AS actual')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => [
                'status' => $r->status,
                'orders' => (int) $r->orders,
                'planned' => (float) $r->planned,
                'actual' => (float) $r->actual,
            ])
            ->all();
    }

    /** Purchase and sales orders mix the same way: value and count per status. */
    private function orderMix($query, Carbon $from, Carbon $to): array
    {
        return $query
            ->whereBetween('order_date', [$from, $to])
            ->selectRaw('status, COUNT(*) AS orders, COALESCE(SUM(total_amount), 0) AS amount')
            ->groupBy('status')
            ->get()
            ->map(fn ($r) => ['status' => $r->status, 'orders' => (int) $r->orders, 'amount' => (float) $r->amount])
            ->all();
    }

    private function movements(Carbon $from, Carbon $to): array
    {
        $rows = StockMovement::query()
            ->whereBetween('created_at', [$from, $to])
            ->selectRaw('document_type, COUNT(*) AS movements, COALESCE(SUM(total_cost), 0) AS value')
            ->groupBy('document_type')
            ->orderByDesc('value')
            ->get()
            ->map(fn ($r) => [
                'document_type' => (string) $r->document_type,
                'movements' => (int) $r->movements,
                'value' => (float) $r->value,
            ])
            ->all();

        return [
            'rows' => $rows,
            'total' => array_sum(array_column($rows, 'movements')),
            'value' => array_sum(array_column($rows, 'value')),
        ];
    }

    private function topItems(Carbon $from, Carbon $to): array
    {
        return ProductionOrder::query()
            ->whereBetween(self::PRODUCTION_DATE, [$from, $to])
            ->join('items', 'items.id', '=', 'production_orders.item_id')
            ->selectRaw('items.id, items.code, items.name, items.type, COUNT(*) AS orders, COALESCE(SUM(production_orders.planned_quantity), 0) AS planned, COALESCE(SUM(production_orders.actual_quantity), 0) AS actual, COALESCE(SUM(production_orders.scrap_quantity), 0) AS scrap')
            ->groupBy('items.id', 'items.code', 'items.name', 'items.type')
            ->orderByDesc('actual')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'type' => $r->type,
                'orders' => (int) $r->orders,
                'planned' => (float) $r->planned,
                'actual' => (float) $r->actual,
                'scrap' => (float) $r->scrap,
                // Against plan, so a run that over-delivered reads over 100%.
                'attainment' => (float) $r->planned > 0 ? ((float) $r->actual / (float) $r->planned) * 100 : null,
            ])
            ->all();
    }

    /* ── the factory right now ───────────────────────────────────────────── */

    private function stock(): array
    {
        $row = StockBalance::query()
            ->selectRaw('COALESCE(SUM(quantity), 0) AS quantity, COALESCE(SUM(total_value), 0) AS value, COALESCE(SUM(reserved_quantity), 0) AS reserved, COUNT(DISTINCT item_id) AS items')
            ->first();

        return [
            'quantity' => (float) $row->quantity,
            'value' => (float) $row->value,
            'reserved' => (float) $row->reserved,
            'items' => (int) $row->items,
        ];
    }

    private function openWork(): array
    {
        return [
            'items' => Item::query()->where('is_active', true)->count(),
            'suppliers' => Supplier::query()->count(),
            'customers' => Customer::query()->count(),
            'work_centers' => WorkCenter::query()->where('is_active', true)->count(),
            'purchase_orders' => PurchaseOrder::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'sales_orders' => SalesOrder::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'production_orders' => ProductionOrder::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
            'ncrs' => Ncr::query()->whereNot('status', 'closed')->count(),
            'maintenance_orders' => MaintenanceOrder::query()->whereNotIn('status', ['closed', 'cancelled'])->count(),
        ];
    }

    /**
     * Items whose total on-hand has fallen to or below the level they are meant to
     * be reordered at. The level lives on the item, the quantity on the balances,
     * so this is a group-and-compare rather than a column against a column.
     */
    private function lowStock(): array
    {
        return Item::query()
            ->where('items.is_active', true)
            ->where('items.reorder_level', '>', 0)
            ->leftJoin('stock_balances', 'stock_balances.item_id', '=', 'items.id')
            ->selectRaw('items.id, items.code, items.name, items.reorder_level, items.reorder_quantity, COALESCE(SUM(stock_balances.quantity), 0) AS on_hand')
            ->groupBy('items.id', 'items.code', 'items.name', 'items.reorder_level', 'items.reorder_quantity')
            ->havingRaw('COALESCE(SUM(stock_balances.quantity), 0) <= items.reorder_level')
            ->orderByRaw('COALESCE(SUM(stock_balances.quantity), 0) - items.reorder_level')
            ->limit(8)
            ->get()
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'code' => $r->code,
                'name' => $r->name,
                'on_hand' => (float) $r->on_hand,
                'reorder_level' => (float) $r->reorder_level,
                'reorder_quantity' => (float) $r->reorder_quantity,
                'shortfall' => max(0, (float) $r->reorder_level - (float) $r->on_hand),
            ])
            ->all();
    }
}

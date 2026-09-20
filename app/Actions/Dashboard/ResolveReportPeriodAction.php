<?php

namespace App\Actions\Dashboard;

use Carbon\Carbon;

/**
 * Turn a period preset (or a custom pair of dates) into a [from, to] range.
 *
 * Ported from App\Http\Livewire\Admin\Dashboard\PortfolioActivity::range(). Lives
 * on its own because the dashboard, the reports screens and any scheduled digest
 * all need the same answer for "last 30 days", and three copies of that would
 * drift.
 */
class ResolveReportPeriodAction
{
    /** @return array<string, string> */
    public static function presets(): array
    {
        return [
            'today' => 'Today',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            'this_month' => 'This month',
            'last_month' => 'Last month',
            'this_year' => 'This year',
        ];
    }

    public const GRANULARITIES = ['auto', 'day', 'week', 'month', 'year'];

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    public function handle(string $period, ?string $fromDate = null, ?string $toDate = null): array
    {
        $today = Carbon::today();

        if ($period === 'custom' && $fromDate && $toDate) {
            $from = Carbon::parse($fromDate)->startOfDay();
            $to = Carbon::parse($toDate)->endOfDay();

            // A reversed custom range is treated as if it were typed the right way
            // round rather than producing an empty report.
            return $from->lte($to)
                ? [$from, $to]
                : [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return match ($period) {
            'today' => [$today->copy()->startOfDay(), $today->copy()->endOfDay()],
            '7d' => [$today->copy()->subDays(6)->startOfDay(), $today->copy()->endOfDay()],
            'this_month' => [$today->copy()->startOfMonth(), $today->copy()->endOfDay()],
            'last_month' => [
                $today->copy()->subMonthNoOverflow()->startOfMonth(),
                $today->copy()->subMonthNoOverflow()->endOfMonth(),
            ],
            'this_year' => [$today->copy()->startOfYear(), $today->copy()->endOfDay()],
            // 30d, and anything unrecognised, fall back to the default window.
            default => [$today->copy()->subDays(29)->startOfDay(), $today->copy()->endOfDay()],
        };
    }
}

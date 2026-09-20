<?php

namespace App\Http\Controllers\Api\V1\Factory;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

/**
 * The figures a register opens with, for any register.
 *
 * Every list screen wants the same thing above the table — how many records there
 * are and how they are split — and every one of them would otherwise need its own
 * endpoint to say so. The split comes from the table's own status column: its enum
 * already lists the states a record can be in, so the breakdown is derived rather
 * than configured, and a state added to a migration appears here on its own.
 */
class StatsController extends BaseController
{
    /** The column that best describes where a record stands, in order of preference. */
    private const STATE_COLUMNS = ['status', 'result', 'severity', 'criticality', 'type', 'level'];

    /**
     * How a state reads. Anything unlisted is left neutral rather than guessed at —
     * a wrong colour on a dashboard is worse than no colour.
     */
    private const TONES = [
        'g' => ['active', 'received', 'closed', 'completed', 'delivered', 'paid', 'approved', 'accepted', 'resolved', 'posted', 'shipped', 'invoiced', 'certified'],
        'r' => ['overdue', 'rejected', 'blocked', 'cancelled', 'failed', 'critical', 'decommissioned', 'terminated'],
        'o' => ['draft', 'pending', 'open', 'submitted', 'requested', 'investigating', 'on_hold', 'due', 'in_progress', 'partially_received', 'partially_paid', 'major'],
    ];

    public function show(Request $request, string $resource)
    {
        abort_unless(isset(ExportController::RESOURCES[$resource]), 404, "No stats for '{$resource}'.");

        $class = ExportController::RESOURCES[$resource];
        $model = new $class;
        $table = $model->getTable();

        $base = fn () => tap($class::query(), fn ($q) => $this->applyScopes($q, $request));

        $stats = [[
            'label' => 'Total',
            'icon' => 'mdi-view-grid-outline',
            'value' => $base()->count(),
        ]];

        $column = $this->stateColumn($table);

        if ($column !== null) {
            $counts = $base()
                ->selectRaw("{$column} AS state, COUNT(*) AS total")
                ->groupBy($column)
                ->orderByDesc('total')
                ->limit(4)
                ->get();

            foreach ($counts as $row) {
                if ($row->state === null || $row->state === '') {
                    continue;
                }

                $stats[] = [
                    'label' => Str::headline((string) $row->state),
                    'value' => (int) $row->total,
                    'dot' => $this->tone((string) $row->state),
                ];
            }
        }

        return $this->success(['resource' => $resource, 'stats' => $stats]);
    }

    private function stateColumn(string $table): ?string
    {
        $columns = Schema::getColumns($table);

        foreach (self::STATE_COLUMNS as $candidate) {
            foreach ($columns as $column) {
                // Only an enum: grouping a free-text column produces one bar per row.
                if ($column['name'] === $candidate && str_starts_with($column['type'], 'enum(')) {
                    return $candidate;
                }
            }
        }

        return null;
    }

    private function tone(string $state): string
    {
        foreach (self::TONES as $dot => $states) {
            if (in_array(strtolower($state), $states, true)) {
                return $dot;
            }
        }

        return 'b';
    }
}

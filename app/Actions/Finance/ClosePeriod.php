<?php

namespace App\Actions\Finance;

use App\Models\Journal;
use App\Models\PeriodClose;
use App\Services\AuditService;
use Illuminate\Validation\ValidationException;

class ClosePeriod
{
    public function execute(int $fiscalPeriodId, string $module, int $closedBy): PeriodClose
    {
        // Check for unposted journals in this period
        $unposted = Journal::where('fiscal_period_id', $fiscalPeriodId)
            ->where('status', '!=', 'posted')
            ->count();

        if ($unposted > 0) {
            throw ValidationException::withMessages([
                'period' => "Cannot close: {$unposted} unposted journal(s) exist for this period.",
            ]);
        }

        $close = PeriodClose::create([
            'fiscal_period_id' => $fiscalPeriodId,
            'module' => $module,
            'closed_by' => $closedBy,
            'closed_at' => now(),
            'is_locked' => true,
        ]);

        AuditService::log('closed', 'finance', $close);

        return $close;
    }
}

<?php
namespace App\Actions\Production;

use App\Models\ProductionOrder;
use App\Services\AuditService;

class StartProductionOrder
{
    public function execute(ProductionOrder $po): ProductionOrder
    {
        $old = $po->toArray();
        $po->update(['status' => 'in_progress', 'actual_start_date' => now()]);

        // Start first operation job
        $po->operationJobs()->where('status', 'pending')->oldest('sequence')->first()?->update(['status' => 'in_progress']);

        AuditService::log('started', 'production', $po, $old, ['status' => 'in_progress']);
        return $po;
    }
}
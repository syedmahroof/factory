<?php
namespace App\Actions\Maintenance;

use App\Models\MaintenanceOrder;
use App\Models\DowntimeEvent;
use App\Services\AuditService;

class CompleteMaintenanceOrder
{
    public function execute(MaintenanceOrder $order, array $completionData = []): MaintenanceOrder
    {
        $old = $order->toArray();
        $order->update(array_merge([
            'status' => 'completed',
            'completed_at' => now(),
        ], $completionData));

        // Record downtime if asset was involved
        if ($order->asset_id && isset($completionData['actual_hours'])) {
            DowntimeEvent::create([
                'asset_id' => $order->asset_id,
                'maintenance_order_id' => $order->id,
                'start_time' => $order->scheduled_date,
                'end_time' => now(),
                'duration_hours' => $completionData['actual_hours'],
                'type' => $order->type,
                'reason' => $order->title,
            ]);
        }

        AuditService::log('completed', 'maintenance', $order, $old, ['status' => 'completed']);
        return $order;
    }
}
<?php
namespace App\Actions\Maintenance;

use App\Models\MaintenanceOrder;
use App\Services\NumberGenerator;
use App\Services\AuditService;

class CreateMaintenanceOrder
{
    public function execute(array $data): MaintenanceOrder
    {
        $data['wo_number'] = NumberGenerator::next('maintenance_order', 'WO');
        $data['status'] = 'open';
        $order = MaintenanceOrder::create($data);
        AuditService::log('created', 'maintenance', $order, null, $data);
        return $order;
    }
}
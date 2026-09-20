<?php
namespace App\Actions\Production;

use App\Models\ProductionOrder;
use App\Models\OperationJob;
use App\Services\NumberGenerator;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateProductionOrder
{
    public function execute(array $data): ProductionOrder
    {
        return DB::transaction(function () use ($data) {
            $data['number'] = NumberGenerator::next('production_order', 'MO');
            $data['status'] = 'draft';

            $po = ProductionOrder::create($data);

            // Auto-create operation jobs from routing if available
            if ($po->routing_id) {
                $routing = $po->routing()->with('operations')->first();
                if ($routing) {
                    foreach ($routing->operations as $index => $op) {
                        OperationJob::create([
                            'production_order_id' => $po->id,
                            'operation_id' => $op->id,
                            'work_center_id' => $op->work_center_id,
                            'planned_quantity' => $po->planned_quantity,
                            'sequence' => $op->sequence ?? ($index + 1) * 10,
                            'status' => 'pending',
                        ]);
                    }
                }
            }

            AuditService::log('created', 'production', $po, null, $data);
            return $po;
        });
    }
}
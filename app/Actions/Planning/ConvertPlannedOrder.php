<?php
namespace App\Actions\Planning;

use App\Models\PlannedOrder;
use App\Actions\{Procurement\CreatePurchaseOrder, Production\CreateProductionOrder};

class ConvertPlannedOrder
{
    public function execute(PlannedOrder $order): PlannedOrder
    {
        $data = [
            'item_id' => $order->item_id,
            'plant_id' => $order->plant_id,
            'planned_quantity' => $order->planned_quantity,
            'planned_start_date' => $order->planned_start_date,
            'planned_end_date' => $order->required_date,
        ];

        if ($order->order_type === 'purchase') {
            $po = (new CreatePurchaseOrder())->execute([
                'supplier_id' => null,
                'plant_id' => $order->plant_id,
                'order_date' => now(),
                'expected_date' => $order->required_date,
                'lines' => [['item_id' => $order->item_id, 'quantity' => $order->planned_quantity, 'unit_price' => 0]],
            ]);
            $order->update(['status' => 'converted', 'converted_order_id' => $po->id, 'converted_order_type' => get_class($po)]);
        } elseif ($order->order_type === 'production') {
            $po = (new CreateProductionOrder())->execute($data);
            $order->update(['status' => 'converted', 'converted_order_id' => $po->id, 'converted_order_type' => get_class($po)]);
        }

        return $order;
    }
}
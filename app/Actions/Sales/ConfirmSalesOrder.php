<?php
namespace App\Actions\Sales;

use App\Models\SalesOrder;
use App\Services\AuditService;

class ConfirmSalesOrder
{
    public function execute(SalesOrder $order): SalesOrder
    {
        $old = $order->toArray();
        $order->update(['status' => 'confirmed', 'confirmed_at' => now()]);

        // Reserve stock for each line
        foreach ($order->lines()->get() as $line) {
            \App\Models\StockBalance::where('item_id', $line->item_id)
                ->where('warehouse_id', $order->warehouse_id ?? 1)
                ->increment('reserved_quantity', $line->quantity);
        }

        AuditService::log('confirmed', 'sales', $order, $old, ['status' => 'confirmed']);
        return $order;
    }
}
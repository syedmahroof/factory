<?php
namespace App\Actions\Procurement;

use App\Models\PurchaseOrder;
use App\Services\AuditService;

class ApprovePurchaseOrder
{
    public function execute(PurchaseOrder $po): PurchaseOrder
    {
        $old = $po->toArray();
        $po->update(['status' => 'approved', 'approved_at' => now()]);
        AuditService::log('approved', 'procurement', $po, $old, ['status' => 'approved']);
        return $po;
    }
}
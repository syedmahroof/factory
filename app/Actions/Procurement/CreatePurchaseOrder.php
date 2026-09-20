<?php
namespace App\Actions\Procurement;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderLine;
use App\Services\NumberGenerator;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreatePurchaseOrder
{
    public function execute(array $data): PurchaseOrder
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['number'] = NumberGenerator::next('purchase_order', 'PO');
            $data['status'] = 'draft';
            $data['total_amount'] = collect($lines)->sum(fn($l) => $l['quantity'] * ($l['unit_price'] ?? 0));
            $data['tax_amount'] = collect($lines)->sum(fn($l) => ($l['quantity'] * ($l['unit_price'] ?? 0) * ($l['tax_rate'] ?? 0)) / 100);
            $data['net_amount'] = $data['total_amount'] + $data['tax_amount'];

            $po = PurchaseOrder::create($data);

            foreach ($lines as $line) {
                $line['po_id'] = $po->id;
                $line['line_total'] = $line['quantity'] * ($line['unit_price'] ?? 0);
                PurchaseOrderLine::create($line);
            }

            AuditService::log('created', 'procurement', $po, null, $data);
            return $po;
        });
    }
}
<?php
namespace App\Actions\Sales;

use App\Models\SalesOrder;
use App\Models\SalesOrderLine;
use App\Services\NumberGenerator;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateSalesOrder
{
    public function execute(array $data): SalesOrder
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['number'] = NumberGenerator::next('sales_order', 'SO');
            $data['status'] = 'draft';
            $data['subtotal'] = collect($lines)->sum(fn($l) => $l['quantity'] * ($l['unit_price'] ?? 0));
            $data['tax_amount'] = collect($lines)->sum(fn($l) => ($l['quantity'] * ($l['unit_price'] ?? 0) * ($l['tax_rate'] ?? 0)) / 100);
            $data['total_amount'] = $data['subtotal'] + $data['tax_amount'];

            $order = SalesOrder::create($data);

            foreach ($lines as $index => $line) {
                $line['sales_order_id'] = $order->id;
                $line['sequence'] = $index + 1;
                $line['line_total'] = $line['quantity'] * ($line['unit_price'] ?? 0);
                SalesOrderLine::create($line);
            }

            AuditService::log('created', 'sales', $order, null, $data);
            return $order;
        });
    }
}
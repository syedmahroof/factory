<?php
namespace App\Actions\Procurement;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptLine;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Services\NumberGenerator;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateGoodsReceipt
{
    public function execute(array $data): GoodsReceipt
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);

            $data['grn_number'] = NumberGenerator::next('goods_receipt', 'GRN');
            $data['status'] = 'completed';

            $grn = GoodsReceipt::create($data);

            foreach ($lines as $line) {
                $line['goods_receipt_id'] = $grn->id;
                GoodsReceiptLine::create($line);

                // Update stock balances
                $this->updateStock($line, $data['warehouse_id']);
            }

            AuditService::log('created', 'procurement', $grn, null, $data);
            return $grn;
        });
    }

    protected function updateStock(array $line, int $warehouseId): void
    {
        $balance = StockBalance::firstOrCreate(
            ['item_id' => $line['item_id'], 'warehouse_id' => $warehouseId, 'lot_number' => $line['batch_number'] ?? null],
            ['quantity' => 0, 'reserved_quantity' => 0, 'available_quantity' => 0]
        );

        $balance->increment('quantity', $line['accepted_quantity'] ?? $line['received_quantity']);
        $balance->increment('available_quantity', $line['accepted_quantity'] ?? $line['received_quantity']);

        StockMovement::create([
            'item_id' => $line['item_id'],
            'movement_type' => 'receipt',
            'to_warehouse_id' => $warehouseId,
            'quantity' => $line['accepted_quantity'] ?? $line['received_quantity'],
            'uom_id' => $line['uom_id'] ?? null,
            'batch_number' => $line['batch_number'] ?? null,
            'reference_type' => GoodsReceipt::class,
            'reference_id' => $line['goods_receipt_id'],
        ]);
    }
}
<?php

namespace App\Actions\Production;

use App\Models\ProductionOrder;
use App\Models\StockBalance;
use App\Models\StockMovement;
use App\Models\BomLine;
use App\Models\MaterialIssue;
use App\Models\WipBalance;
use Illuminate\Support\Facades\DB;

class CompleteProductionOrder
{
    /**
     * Complete a production order:
     * 1. Backflush BOM components (consume materials)
     * 2. Record finished goods output
     * 3. Update WIP balances
     * 4. Calculate yield and variance
     */
    public function execute(ProductionOrder $order, array $data = []): ProductionOrder
    {
        return DB::transaction(function () use ($order, $data) {
            $actualQuantity = $data['quantity'] ?? $order->quantity;
            $scrapQuantity = $data['scrap_quantity'] ?? 0;
            $outputQuantity = $actualQuantity - $scrapQuantity;

            // 1. Backflush BOM components
            $this->backflushBom($order, $outputQuantity);

            // 2. Receive finished goods to inventory
            $this->receiveFinishedGoods($order, $outputQuantity, $scrapQuantity);

            // 3. Update WIP to zero
            WipBalance::where('production_order_id', $order->id)
                ->where('quantity', '>', 0)
                ->update(['quantity' => 0, 'total_cost' => 0]);

            // 4. Update order status
            $order->update([
                'status' => 'completed',
                'completed_at' => now(),
                'completed_by' => auth()->id(),
                'actual_quantity' => $actualQuantity,
                'scrap_quantity' => $scrapQuantity,
                'yield_percent' => $order->quantity > 0 ? round(($outputQuantity / $order->quantity) * 100, 2) : 0,
            ]);

            return $order;
        });
    }

    /**
     * Auto-consume BOM components based on output quantity.
     * This is the backflush logic.
     */
    protected function backflushBom(ProductionOrder $order, float $outputQuantity): void
    {
        // Get the BOM for this item
        $bom = $order->item?->bom;
        if (!$bom) return;

        $bomLines = BomLine::where('bom_id', $bom->id)->get();

        foreach ($bomLines as $line) {
            $requiredQty = $outputQuantity * ($line->quantity ?? 1);
            $scrapFactor = 1 + (($line->scrap_percent ?? 0) / 100);
            $totalConsumption = $requiredQty * $scrapFactor;

            // Check stock availability
            $balance = StockBalance::where('item_id', $line->item_id)
                ->where('warehouse_id', $order->warehouse_id ?? 1)
                ->lockForUpdate()
                ->first();

            if (!$balance || $balance->quantity_available < $totalConsumption) {
                // Log shortage but don't block completion (configurable)
                continue;
            }

            // Consume from inventory
            $balance->quantity_on_hand -= $totalConsumption;
            $balance->quantity_available -= $totalConsumption;
            $balance->total_value -= $totalConsumption * ($line->item->standard_cost ?? 0);
            if ($balance->total_value < 0) $balance->total_value = 0;
            $balance->save();

            // Create stock movement
            StockMovement::create([
                'item_id' => $line->item_id,
                'warehouse_id' => $order->warehouse_id ?? 1,
                'movement_type' => 'issue',
                'quantity' => $totalConsumption,
                'unit_cost' => $line->item->standard_cost ?? 0,
                'total_cost' => $totalConsumption * ($line->item->standard_cost ?? 0),
                'reference_type' => ProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => "Backflush for PO {$order->number}",
                'user_id' => auth()->id(),
                'posted_at' => now(),
            ]);

            // Record material issue
            MaterialIssue::create([
                'production_order_id' => $order->id,
                'item_id' => $line->item_id,
                'quantity' => $totalConsumption,
                'unit_cost' => $line->item->standard_cost ?? 0,
                'warehouse_id' => $order->warehouse_id ?? 1,
                'issued_at' => now(),
                'issued_by' => auth()->id(),
            ]);
        }
    }

    /**
     * Receive finished goods into inventory.
     */
    protected function receiveFinishedGoods(ProductionOrder $order, float $outputQuantity, float $scrapQuantity): void
    {
        if ($outputQuantity <= 0) return;

        $warehouseId = $order->warehouse_id ?? 1;
        $unitCost = $order->item->standard_cost ?? 0;

        // Create or update stock balance
        $balance = StockBalance::where('item_id', $order->item_id)
            ->where('warehouse_id', $warehouseId)
            ->lockForUpdate()
            ->first();

        if (!$balance) {
            $balance = StockBalance::create([
                'item_id' => $order->item_id,
                'warehouse_id' => $warehouseId,
                'quantity_on_hand' => 0,
                'quantity_reserved' => 0,
                'quantity_available' => 0,
                'total_value' => 0,
                'reorder_point' => 0,
            ]);
        }

        $balance->quantity_on_hand += $outputQuantity;
        $balance->quantity_available += $outputQuantity;
        $balance->total_value += $outputQuantity * $unitCost;
        $balance->save();

        // Stock movement for finished goods
        StockMovement::create([
            'item_id' => $order->item_id,
            'warehouse_id' => $warehouseId,
            'movement_type' => 'receipt',
            'quantity' => $outputQuantity,
            'unit_cost' => $unitCost,
            'total_cost' => $outputQuantity * $unitCost,
            'reference_type' => ProductionOrder::class,
            'reference_id' => $order->id,
            'notes' => "Production output from {$order->number}",
            'user_id' => auth()->id(),
            'posted_at' => now(),
        ]);

        // Scrap record
        if ($scrapQuantity > 0) {
            StockMovement::create([
                'item_id' => $order->item_id,
                'warehouse_id' => $warehouseId,
                'movement_type' => 'adjustment',
                'quantity' => -$scrapQuantity,
                'unit_cost' => $unitCost,
                'total_cost' => $scrapQuantity * $unitCost,
                'reference_type' => ProductionOrder::class,
                'reference_id' => $order->id,
                'notes' => "Scrap from {$order->number}",
                'user_id' => auth()->id(),
                'posted_at' => now(),
            ]);
        }
    }
}

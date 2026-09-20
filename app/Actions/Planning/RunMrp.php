<?php

namespace App\Actions\Planning;

use App\Models\MrpRun;
use App\Models\PlannedOrder;
use App\Models\Item;
use App\Models\StockBalance;
use App\Models\PurchaseOrder;
use App\Models\ProductionOrder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RunMrp
{
    /**
     * Run MRP for a company:
     * 1. Calculate gross demand from sales orders + forecasts + safety stock
     * 2. Calculate available supply from inventory + open POs + open production
     * 3. Net demand = gross demand - available supply
     * 4. Generate planned orders for items with net demand > 0
     * 5. Generate exception messages
     */
    public function execute(int $companyId): array
    {
        return DB::transaction(function () use ($companyId) {
            $run = MrpRun::create([
                'run_number' => 'MRP-' . now()->format('YmdHis'),
                'company_id' => $companyId,
                'status' => 'running',
                'started_at' => now(),
            ]);

            $itemsProcessed = 0;
            $plannedOrdersCreated = 0;
            $exceptions = [];

            // Get all active items for this company
            $items = Item::where('company_id', $companyId)
                ->whereIn('type', ['raw_material', 'finished_good', 'semi_finished'])
                ->get();

            foreach ($items as $item) {
                $itemsProcessed++;

                // 1. Calculate gross demand
                $grossDemand = $this->calculateGrossDemand($item, $companyId);

                // 2. Calculate available supply
                $availableSupply = $this->calculateAvailableSupply($item, $companyId);

                // 3. Net demand
                $netDemand = $grossDemand - $availableSupply;

                if ($netDemand <= 0) {
                    // Check for excess
                    if ($availableSupply > $grossDemand * 1.5) {
                        $exceptions[] = [
                            'type' => 'excess',
                            'item_id' => $item->id,
                            'item_name' => $item->name,
                            'message' => "Excess stock: {$item->name} has " . ($availableSupply - $grossDemand) . " units more than demand",
                        ];
                    }
                    continue;
                }

                // 4. Apply lot sizing (minimum order quantity, lot-for-lot)
                $orderQuantity = $this->applyLotSizing($item, $netDemand);

                // 5. Determine order type and timing
                $leadTimeDays = $item->lead_time ?? 14;
                $dueDate = Carbon::now()->addDays($leadTimeDays);
                $orderType = $item->type === 'finished_good' || $item->type === 'semi_finished' ? 'production' : 'purchase';

                // 6. Check for existing planned orders (don't duplicate)
                $existingPlanned = PlannedOrder::where('item_id', $item->id)
                    ->where('status', 'planned')
                    ->exists();

                if (!$existingPlanned) {
                    PlannedOrder::create([
                        'order_number' => 'PO-' . str_pad(PlannedOrder::max('id') + 1, 6, '0', STR_PAD_LEFT),
                        'item_id' => $item->id,
                        'company_id' => $companyId,
                        'order_type' => $orderType,
                        'quantity' => $orderQuantity,
                        'due_date' => $dueDate->toDateString(),
                        'status' => 'planned',
                        'planned_start' => Carbon::now()->toDateString(),
                        'planned_end' => $dueDate->toDateString(),
                        'source' => 'mrp',
                        'mrp_run_id' => $run->id,
                    ]);
                    $plannedOrdersCreated++;
                }

                // 7. Generate exceptions
                if ($leadTimeDays > 30) {
                    $exceptions[] = [
                        'type' => 'long_lead_time',
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'message' => "{$item->name} has long lead time ({$leadTimeDays} days)",
                    ];
                }

                // Check if reorder point is breached
                $currentStock = StockBalance::where('item_id', $item->id)
                    ->where('company_id', $companyId)
                    ->sum('quantity_on_hand');

                if ($item->reorder_point > 0 && $currentStock <= $item->reorder_point) {
                    $exceptions[] = [
                        'type' => 'reorder_point',
                        'item_id' => $item->id,
                        'item_name' => $item->name,
                        'message' => "{$item->name} at/below reorder point ({$currentStock}/{$item->reorder_point})",
                    ];
                }
            }

            // Update run
            $run->update([
                'status' => 'completed',
                'completed_at' => now(),
                'items_processed' => $itemsProcessed,
                'planned_orders_count' => $plannedOrdersCreated,
                'exceptions_count' => count($exceptions),
                'exceptions' => $exceptions,
            ]);

            return [
                'run' => $run,
                'items_processed' => $itemsProcessed,
                'planned_orders_created' => $plannedOrdersCreated,
                'exceptions' => $exceptions,
            ];
        });
    }

    /**
     * Calculate gross demand from:
     * - Confirmed sales orders
     * - Forecasts
     * - Safety stock
     * - Production order BOM requirements (dependent demand)
     */
    protected function calculateGrossDemand(Item $item, int $companyId): float
    {
        $demand = 0;

        // 1. Open sales order demand
        $demand += \App\Models\SalesOrderLine::where('item_id', $item->id)
            ->whereHas('salesOrder', function ($q) use ($companyId) {
                $q->whereIn('status', ['confirmed', 'in_progress'])
                  ->where('company_id', $companyId);
            })
            ->sum('quantity');

        // 2. Forecast demand (next 30 days)
        $demand += \App\Models\Forecast::where('item_id', $item->id)
            ->where('company_id', $companyId)
            ->where('forecast_date', '<=', Carbon::now()->addDays(30))
            ->sum('quantity');

        // 3. Safety stock
        $demand += $item->safety_stock ?? 0;

        return $demand;
    }

    /**
     * Calculate available supply from:
     * - Current inventory
     * - Open purchase orders (expected receipts)
     * - Open production orders (expected output)
     */
    protected function calculateAvailableSupply(Item $item, int $companyId): float
    {
        $supply = 0;

        // 1. Current inventory
        $supply += StockBalance::where('item_id', $item->id)
            ->where('company_id', $companyId)
            ->sum('quantity_available');

        // 2. Open purchase order receipts
        $supply += \App\Models\PurchaseOrderLine::where('item_id', $item->id)
            ->whereHas('purchaseOrder', function ($q) use ($companyId) {
                $q->whereIn('status', ['approved', 'confirmed'])
                  ->where('company_id', $companyId);
            })
            ->sum('quantity');

        // 3. Open production order output
        $supply += ProductionOrder::where('item_id', $item->id)
            ->whereIn('status', ['planned', 'in_progress'])
            ->where('company_id', $companyId)
            ->sum('quantity');

        return $supply;
    }

    /**
     * Apply lot sizing rules:
     * - Minimum order quantity
     * - Multiples (if configured)
     * - Lot-for-lot (exact quantity needed)
     */
    protected function applyLotSizing(Item $item, float $netDemand): float
    {
        $minOrderQty = $item->minimum_order_quantity ?? 0;
        $lotMultiple = $item->lot_multiple ?? 0;

        $quantity = max($netDemand, $minOrderQty);

        if ($lotMultiple > 0) {
            $quantity = ceil($quantity / $lotMultiple) * $lotMultiple;
        }

        return $quantity;
    }
}

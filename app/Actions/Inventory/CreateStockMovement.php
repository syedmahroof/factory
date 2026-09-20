<?php

namespace App\Actions\Inventory;

use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class CreateStockMovement
{
    /**
     * Create a stock movement atomically.
     * Uses SELECT FOR UPDATE to prevent race conditions on stock balances.
     */
    public function execute(array $data): StockMovement
    {
        return DB::transaction(function () use ($data) {
            $movementType = $data['movement_type'];
            $quantity = $data['quantity'];
            $unitCost = $data['unit_cost'] ?? 0;
            $totalCost = $quantity * $unitCost;

            // Lock the stock balance row to prevent concurrent modifications
            $balance = StockBalance::where('item_id', $data['item_id'])
                ->where('warehouse_id', $data['warehouse_id'])
                ->where('bin_id', $data['bin_id'] ?? null)
                ->lockForUpdate()
                ->first();

            if (!$balance) {
                // Create new balance row
                $balance = StockBalance::create([
                    'item_id' => $data['item_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'bin_id' => $data['bin_id'] ?? null,
                    'lot_id' => $data['lot_id'] ?? null,
                    'serial_id' => $data['serial_id'] ?? null,
                    'quantity_on_hand' => 0,
                    'quantity_reserved' => 0,
                    'quantity_available' => 0,
                    'total_value' => 0,
                    'reorder_point' => 0,
                    'company_id' => auth()->user()->company_id ?? null,
                ]);
            }

            // Validate sufficient stock for issues
            if (in_array($movementType, ['issue', 'transfer'])) {
                if ($balance->quantity_available < $quantity) {
                    throw new \Exception("Insufficient stock. Available: {$balance->quantity_available}, Requested: {$quantity}");
                }
            }

            // Update balance based on movement type
            switch ($movementType) {
                case 'receipt':
                case 'return':
                    $balance->quantity_on_hand += $quantity;
                    $balance->total_value += $totalCost;
                    break;

                case 'issue':
                    $balance->quantity_on_hand -= $quantity;
                    $balance->total_value -= $totalCost;
                    // Adjust total value proportionally to avoid negative
                    if ($balance->total_value < 0) $balance->total_value = 0;
                    break;

                case 'transfer':
                    // Source warehouse: decrease
                    $balance->quantity_on_hand -= $quantity;
                    $balance->total_value -= $totalCost;
                    if ($balance->total_value < 0) $balance->total_value = 0;

                    // Destination warehouse: increase (create if needed)
                    if (!empty($data['to_warehouse_id'])) {
                        $destBalance = StockBalance::where('item_id', $data['item_id'])
                            ->where('warehouse_id', $data['to_warehouse_id'])
                            ->where('bin_id', $data['to_bin_id'] ?? null)
                            ->lockForUpdate()
                            ->first();

                        if (!$destBalance) {
                            $destBalance = StockBalance::create([
                                'item_id' => $data['item_id'],
                                'warehouse_id' => $data['to_warehouse_id'],
                                'bin_id' => $data['to_bin_id'] ?? null,
                                'quantity_on_hand' => 0,
                                'quantity_reserved' => 0,
                                'quantity_available' => 0,
                                'total_value' => 0,
                                'reorder_point' => 0,
                                'company_id' => auth()->user()->company_id ?? null,
                            ]);
                        }

                        $destBalance->quantity_on_hand += $quantity;
                        $destBalance->total_value += $totalCost;
                        $destBalance->save();
                    }
                    break;

                case 'adjustment':
                    // Positive adjustment = increase, negative = decrease
                    if ($quantity > 0) {
                        $balance->quantity_on_hand += abs($quantity);
                        $balance->total_value += abs($totalCost);
                    } else {
                        $balance->quantity_on_hand -= abs($quantity);
                        $balance->total_value -= abs($totalCost);
                        if ($balance->total_value < 0) $balance->total_value = 0;
                    }
                    break;
            }

            // Recalculate available quantity
            $balance->quantity_available = $balance->quantity_on_hand - $balance->quantity_reserved;
            $balance->save();

            // Create movement record
            $movement = StockMovement::create([
                'item_id' => $data['item_id'],
                'warehouse_id' => $data['warehouse_id'],
                'bin_id' => $data['bin_id'] ?? null,
                'movement_type' => $movementType,
                'quantity' => $quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'from_warehouse_id' => $movementType === 'transfer' ? $data['warehouse_id'] : null,
                'to_warehouse_id' => $data['to_warehouse_id'] ?? null,
                'reference_type' => $data['reference_type'] ?? null,
                'reference_id' => $data['reference_id'] ?? null,
                'lot_id' => $data['lot_id'] ?? null,
                'serial_id' => $data['serial_id'] ?? null,
                'notes' => $data['notes'] ?? null,
                'user_id' => auth()->id(),
                'posted_at' => now(),
                'company_id' => auth()->user()->company_id ?? null,
            ]);

            return $movement;
        });
    }
}

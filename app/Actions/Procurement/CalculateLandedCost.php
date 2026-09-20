<?php
namespace App\Actions\Procurement;

use App\Models\{LandedCost, GoodsReceiptLine, StockMovement};
use Illuminate\Support\Facades\DB;

class CalculateLandedCost
{
    public function execute(array $data): LandedCost
    {
        return DB::transaction(function () use ($data) {
            $total = ($data['freight'] ?? 0) + ($data['duty'] ?? 0) + ($data['insurance'] ?? 0) + ($data['other_charges'] ?? 0);
            $data['total_landed_cost'] = $total;
            $data['status'] = 'draft';
            $landedCost = LandedCost::create($data);

            // Allocate landed cost to received items
            $grnLines = GoodsReceiptLine::where('goods_receipt_id', $data['goods_receipt_id'])->get();
            $method = $data['allocation_method'] ?? 'value';
            $totalBase = $grnLines->sum(fn($l) => $method === 'quantity' ? $l->received_quantity : ($l->received_quantity * ($l->unit_price ?? 0)));

            if ($totalBase > 0) {
                foreach ($grnLines as $line) {
                    $base = $method === 'quantity' ? $line->received_quantity : ($line->received_quantity * ($line->unit_price ?? 0));
                    $allocatedCost = ($base / $totalBase) * $total;
                    // Update stock cost with allocated landed cost
                    if ($line->received_quantity > 0) {
                        $costPerUnit = $allocatedCost / $line->received_quantity;
                        StockMovement::where('reference_type', GoodsReceiptLine::class)
                            ->where('reference_id', $line->id)
                            ->update(['unit_cost' => DB::raw("COALESCE(unit_cost, 0) + {$costPerUnit}")]);
                    }
                }
            }

            return $landedCost;
        });
    }
}
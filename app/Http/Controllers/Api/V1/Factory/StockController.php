<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Actions\Inventory\CreateStockMovement;
use App\Models\StockBalance;
use App\Models\StockMovement;
use Illuminate\Http\Request;

class StockController extends BaseController
{
    public function index(Request $request)
    {
        $query = StockBalance::with(['item', 'warehouse', 'bin']);
        if ($search = $request->get('search')) {
            $query->whereHas('item', fn ($q) => $q->where('name', 'LIKE', "%{$search}%")->orWhere('code', 'LIKE', "%{$search}%"));
        }
        if ($warehouseId = $request->get('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        return $this->success($query->orderBy('id', 'desc')->paginate($request->get('per_page', 25)));
    }

    public function movements(Request $request)
    {
        // A movement has a from- and a to-warehouse, not one `warehouse`.
        return $this->success($this->paginated(StockMovement::query(), $request, ['item', 'fromWarehouse', 'toWarehouse']));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'item_id' => 'required|exists:items,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'movement_type' => 'required|in:receipt,issue,transfer,adjustment',
            'quantity' => 'required|numeric|min:0.01',
            'unit_cost' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
        ]);
        $result = app(CreateStockMovement::class)->execute($validated);

        return $this->success($result, 'Stock movement recorded', 201);
    }
}

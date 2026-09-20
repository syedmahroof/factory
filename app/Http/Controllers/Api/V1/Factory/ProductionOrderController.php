<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\ProductionOrder;
use Illuminate\Http\Request;

class ProductionOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = ProductionOrder::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        // The register names the item this belongs to, so the relation is loaded.
        return $this->success($this->paginated($query, $request, ['item']));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new ProductionOrder);
        $production_order = ProductionOrder::create($validated);

        return $this->success($production_order, 'Created', 201);
    }

    public function show(ProductionOrder $production_order)
    {
        return $this->success($production_order);
    }

    public function update(Request $request, ProductionOrder $production_order)
    {
        $validated = $this->validatedFor($request, $production_order, $production_order->id);
        $production_order->update($validated);

        return $this->success($production_order, 'Updated');
    }

    public function destroy(ProductionOrder $production_order)
    {
        $production_order->delete();

        return $this->success(null, 'Deleted');
    }
}

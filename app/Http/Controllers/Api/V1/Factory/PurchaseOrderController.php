<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\PurchaseOrder;
use Illuminate\Http\Request;

class PurchaseOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = PurchaseOrder::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new PurchaseOrder);
        $purchase_order = PurchaseOrder::create($validated);

        return $this->success($purchase_order, 'Created', 201);
    }

    public function show(PurchaseOrder $purchase_order)
    {
        return $this->success($purchase_order);
    }

    public function update(Request $request, PurchaseOrder $purchase_order)
    {
        $validated = $this->validatedFor($request, $purchase_order, $purchase_order->id);
        $purchase_order->update($validated);

        return $this->success($purchase_order, 'Updated');
    }

    public function destroy(PurchaseOrder $purchase_order)
    {
        $purchase_order->delete();

        return $this->success(null, 'Deleted');
    }
}

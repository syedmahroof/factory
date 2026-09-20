<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\PurchaseRequisition;
use Illuminate\Http\Request;

class PurchaseRequisitionController extends BaseController
{
    public function index(Request $request)
    {
        $query = PurchaseRequisition::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new PurchaseRequisition);
        $purchase_requisition = PurchaseRequisition::create($validated);

        return $this->success($purchase_requisition, 'Created', 201);
    }

    public function show(PurchaseRequisition $purchase_requisition)
    {
        return $this->success($purchase_requisition);
    }

    public function update(Request $request, PurchaseRequisition $purchase_requisition)
    {
        $validated = $this->validatedFor($request, $purchase_requisition, $purchase_requisition->id);
        $purchase_requisition->update($validated);

        return $this->success($purchase_requisition, 'Updated');
    }

    public function destroy(PurchaseRequisition $purchase_requisition)
    {
        $purchase_requisition->delete();

        return $this->success(null, 'Deleted');
    }
}

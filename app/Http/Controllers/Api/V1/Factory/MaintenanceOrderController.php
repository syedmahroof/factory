<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\MaintenanceOrder;
use Illuminate\Http\Request;

class MaintenanceOrderController extends BaseController
{
    public function index(Request $request)
    {
        $query = MaintenanceOrder::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new MaintenanceOrder);
        $maintenance_order = MaintenanceOrder::create($validated);

        return $this->success($maintenance_order, 'Created', 201);
    }

    public function show(MaintenanceOrder $maintenance_order)
    {
        return $this->success($maintenance_order);
    }

    public function update(Request $request, MaintenanceOrder $maintenance_order)
    {
        $validated = $this->validatedFor($request, $maintenance_order, $maintenance_order->id);
        $maintenance_order->update($validated);

        return $this->success($maintenance_order, 'Updated');
    }

    public function destroy(MaintenanceOrder $maintenance_order)
    {
        $maintenance_order->delete();

        return $this->success(null, 'Deleted');
    }
}

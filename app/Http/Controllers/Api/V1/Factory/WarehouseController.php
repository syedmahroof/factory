<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends BaseController
{
    public function index(Request $request)
    {
        $query = Warehouse::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Warehouse);
        $warehouse = Warehouse::create($validated);

        return $this->success($warehouse, 'Created', 201);
    }

    public function show(Warehouse $warehouse)
    {
        return $this->success($warehouse);
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $validated = $this->validatedFor($request, $warehouse, $warehouse->id);
        $warehouse->update($validated);

        return $this->success($warehouse, 'Updated');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();

        return $this->success(null, 'Deleted');
    }
}

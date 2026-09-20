<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends BaseController
{
    public function index(Request $request)
    {
        $query = Supplier::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Supplier);
        $supplier = Supplier::create($validated);

        return $this->success($supplier, 'Created', 201);
    }

    public function show(Supplier $supplier)
    {
        return $this->success($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $this->validatedFor($request, $supplier, $supplier->id);
        $supplier->update($validated);

        return $this->success($supplier, 'Updated');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();

        return $this->success(null, 'Deleted');
    }
}

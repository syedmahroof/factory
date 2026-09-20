<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Shipment;
use Illuminate\Http\Request;

class ShipmentController extends BaseController
{
    public function index(Request $request)
    {
        $query = Shipment::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Shipment);
        $shipment = Shipment::create($validated);

        return $this->success($shipment, 'Created', 201);
    }

    public function show(Shipment $shipment)
    {
        return $this->success($shipment);
    }

    public function update(Request $request, Shipment $shipment)
    {
        $validated = $this->validatedFor($request, $shipment, $shipment->id);
        $shipment->update($validated);

        return $this->success($shipment, 'Updated');
    }

    public function destroy(Shipment $shipment)
    {
        $shipment->delete();

        return $this->success(null, 'Deleted');
    }
}

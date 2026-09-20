<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Plant;
use Illuminate\Http\Request;

class PlantController extends BaseController
{
    public function index(Request $request)
    {
        $query = Plant::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Plant);
        $plant = Plant::create($validated);

        return $this->success($plant, 'Created', 201);
    }

    public function show(Plant $plant)
    {
        return $this->success($plant);
    }

    public function update(Request $request, Plant $plant)
    {
        $validated = $this->validatedFor($request, $plant, $plant->id);
        $plant->update($validated);

        return $this->success($plant, 'Updated');
    }

    public function destroy(Plant $plant)
    {
        $plant->delete();

        return $this->success(null, 'Deleted');
    }
}

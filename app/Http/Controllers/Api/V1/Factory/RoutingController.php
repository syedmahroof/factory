<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Routing;
use Illuminate\Http\Request;

class RoutingController extends BaseController
{
    public function index(Request $request)
    {
        $query = Routing::query();
        $request->merge(['search_fields' => ['number', 'item_id']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Routing);
        $routing = Routing::create($validated);

        return $this->success($routing, 'Created', 201);
    }

    public function show(Routing $routing)
    {
        return $this->success($routing);
    }

    public function update(Request $request, Routing $routing)
    {
        $validated = $this->validatedFor($request, $routing, $routing->id);
        $routing->update($validated);

        return $this->success($routing, 'Updated');
    }

    public function destroy(Routing $routing)
    {
        $routing->delete();

        return $this->success(null, 'Deleted');
    }
}

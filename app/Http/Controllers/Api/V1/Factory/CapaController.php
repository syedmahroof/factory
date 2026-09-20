<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Capa;
use Illuminate\Http\Request;

class CapaController extends BaseController
{
    public function index(Request $request)
    {
        $query = Capa::query();
        $request->merge(['search_fields' => ['capa_number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Capa);
        $capa = Capa::create($validated);

        return $this->success($capa, 'Created', 201);
    }

    public function show(Capa $capa)
    {
        return $this->success($capa);
    }

    public function update(Request $request, Capa $capa)
    {
        $validated = $this->validatedFor($request, $capa, $capa->id);
        $capa->update($validated);

        return $this->success($capa, 'Updated');
    }

    public function destroy(Capa $capa)
    {
        $capa->delete();

        return $this->success(null, 'Deleted');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Uom;
use Illuminate\Http\Request;

class UomController extends BaseController
{
    public function index(Request $request)
    {
        $query = Uom::query();
        $request->merge(['search_fields' => ['name', 'code', 'symbol']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Uom);
        $uom = Uom::create($validated);

        return $this->success($uom, 'Created', 201);
    }

    public function show(Uom $uom)
    {
        return $this->success($uom);
    }

    public function update(Request $request, Uom $uom)
    {
        $validated = $this->validatedFor($request, $uom, $uom->id);
        $uom->update($validated);

        return $this->success($uom, 'Updated');
    }

    public function destroy(Uom $uom)
    {
        $uom->delete();

        return $this->success(null, 'Deleted');
    }
}

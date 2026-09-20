<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Bom;
use Illuminate\Http\Request;

class BomController extends BaseController
{
    public function index(Request $request)
    {
        $query = Bom::query();
        $request->merge(['search_fields' => ['number', 'item_id']]);
        $this->applyScopes($query, $request);

        // The register names the item this belongs to, so the relation is loaded.
        return $this->success($this->paginated($query, $request, ['item']));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Bom);
        $bom = Bom::create($validated);

        return $this->success($bom, 'Created', 201);
    }

    public function show(Bom $bom)
    {
        return $this->success($bom);
    }

    public function update(Request $request, Bom $bom)
    {
        $validated = $this->validatedFor($request, $bom, $bom->id);
        $bom->update($validated);

        return $this->success($bom, 'Updated');
    }

    public function destroy(Bom $bom)
    {
        $bom->delete();

        return $this->success(null, 'Deleted');
    }
}

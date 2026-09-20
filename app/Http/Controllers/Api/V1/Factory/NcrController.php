<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Ncr;
use Illuminate\Http\Request;

class NcrController extends BaseController
{
    public function index(Request $request)
    {
        $query = Ncr::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Ncr);
        $ncr = Ncr::create($validated);

        return $this->success($ncr, 'Created', 201);
    }

    public function show(Ncr $ncr)
    {
        return $this->success($ncr);
    }

    public function update(Request $request, Ncr $ncr)
    {
        $validated = $this->validatedFor($request, $ncr, $ncr->id);
        $ncr->update($validated);

        return $this->success($ncr, 'Updated');
    }

    public function destroy(Ncr $ncr)
    {
        $ncr->delete();

        return $this->success(null, 'Deleted');
    }
}

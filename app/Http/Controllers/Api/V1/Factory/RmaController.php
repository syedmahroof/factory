<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\ReturnMerchandiseAuthorization;
use Illuminate\Http\Request;

class RmaController extends BaseController
{
    public function index(Request $request)
    {
        $query = ReturnMerchandiseAuthorization::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new ReturnMerchandiseAuthorization);
        $rma = ReturnMerchandiseAuthorization::create($validated);

        return $this->success($rma, 'Created', 201);
    }

    public function show(ReturnMerchandiseAuthorization $rma)
    {
        return $this->success($rma);
    }

    public function update(Request $request, ReturnMerchandiseAuthorization $rma)
    {
        $validated = $this->validatedFor($request, $rma, $rma->id);
        $rma->update($validated);

        return $this->success($rma, 'Updated');
    }

    public function destroy(ReturnMerchandiseAuthorization $rma)
    {
        $rma->delete();

        return $this->success(null, 'Deleted');
    }
}

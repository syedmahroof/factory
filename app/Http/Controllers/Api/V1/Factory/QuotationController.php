<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Quotation;
use Illuminate\Http\Request;

class QuotationController extends BaseController
{
    public function index(Request $request)
    {
        $query = Quotation::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Quotation);
        $quotation = Quotation::create($validated);

        return $this->success($quotation, 'Created', 201);
    }

    public function show(Quotation $quotation)
    {
        return $this->success($quotation);
    }

    public function update(Request $request, Quotation $quotation)
    {
        $validated = $this->validatedFor($request, $quotation, $quotation->id);
        $quotation->update($validated);

        return $this->success($quotation, 'Updated');
    }

    public function destroy(Quotation $quotation)
    {
        $quotation->delete();

        return $this->success(null, 'Deleted');
    }
}

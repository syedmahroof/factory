<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\QualityInspection;
use Illuminate\Http\Request;

class InspectionController extends BaseController
{
    public function index(Request $request)
    {
        $query = QualityInspection::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new QualityInspection);
        $inspection = QualityInspection::create($validated);

        return $this->success($inspection, 'Created', 201);
    }

    public function show(QualityInspection $inspection)
    {
        return $this->success($inspection);
    }

    public function update(Request $request, QualityInspection $inspection)
    {
        $validated = $this->validatedFor($request, $inspection, $inspection->id);
        $inspection->update($validated);

        return $this->success($inspection, 'Updated');
    }

    public function destroy(QualityInspection $inspection)
    {
        $inspection->delete();

        return $this->success(null, 'Deleted');
    }
}

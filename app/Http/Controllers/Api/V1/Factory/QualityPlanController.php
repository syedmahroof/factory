<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\QualityPlan;
use Illuminate\Http\Request;

class QualityPlanController extends BaseController
{
    public function index(Request $request)
    {
        $query = QualityPlan::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new QualityPlan);
        $quality_plan = QualityPlan::create($validated);

        return $this->success($quality_plan, 'Created', 201);
    }

    public function show(QualityPlan $quality_plan)
    {
        return $this->success($quality_plan);
    }

    public function update(Request $request, QualityPlan $quality_plan)
    {
        $validated = $this->validatedFor($request, $quality_plan, $quality_plan->id);
        $quality_plan->update($validated);

        return $this->success($quality_plan, 'Updated');
    }

    public function destroy(QualityPlan $quality_plan)
    {
        $quality_plan->delete();

        return $this->success(null, 'Deleted');
    }
}

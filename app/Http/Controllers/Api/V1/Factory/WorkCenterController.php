<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\WorkCenter;
use Illuminate\Http\Request;

class WorkCenterController extends BaseController
{
    public function index(Request $request)
    {
        $query = WorkCenter::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new WorkCenter);
        $work_center = WorkCenter::create($validated);

        return $this->success($work_center, 'Created', 201);
    }

    public function show(WorkCenter $work_center)
    {
        return $this->success($work_center);
    }

    public function update(Request $request, WorkCenter $work_center)
    {
        $validated = $this->validatedFor($request, $work_center, $work_center->id);
        $work_center->update($validated);

        return $this->success($work_center, 'Updated');
    }

    public function destroy(WorkCenter $work_center)
    {
        $work_center->delete();

        return $this->success(null, 'Deleted');
    }
}

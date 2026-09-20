<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Department;
use Illuminate\Http\Request;

class DepartmentController extends BaseController
{
    public function index(Request $request)
    {
        $query = Department::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Department);
        $department = Department::create($validated);

        return $this->success($department, 'Created', 201);
    }

    public function show(Department $department)
    {
        return $this->success($department);
    }

    public function update(Request $request, Department $department)
    {
        $validated = $this->validatedFor($request, $department, $department->id);
        $department->update($validated);

        return $this->success($department, 'Updated');
    }

    public function destroy(Department $department)
    {
        $department->delete();

        return $this->success(null, 'Deleted');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends BaseController
{
    public function index(Request $request)
    {
        $query = Employee::query();
        $request->merge(['search_fields' => ['name', 'employee_number']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Employee);
        $employee = Employee::create($validated);

        return $this->success($employee, 'Created', 201);
    }

    public function show(Employee $employee)
    {
        return $this->success($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $this->validatedFor($request, $employee, $employee->id);
        $employee->update($validated);

        return $this->success($employee, 'Updated');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return $this->success(null, 'Deleted');
    }
}

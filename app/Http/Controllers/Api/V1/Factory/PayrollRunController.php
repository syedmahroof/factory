<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\PayrollRun;
use Illuminate\Http\Request;

class PayrollRunController extends BaseController
{
    public function index(Request $request)
    {
        $query = PayrollRun::query();
        $request->merge(['search_fields' => ['run_number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new PayrollRun);
        $payroll_run = PayrollRun::create($validated);

        return $this->success($payroll_run, 'Created', 201);
    }

    public function show(PayrollRun $payroll_run)
    {
        return $this->success($payroll_run);
    }

    public function update(Request $request, PayrollRun $payroll_run)
    {
        $validated = $this->validatedFor($request, $payroll_run, $payroll_run->id);
        $payroll_run->update($validated);

        return $this->success($payroll_run, 'Updated');
    }

    public function destroy(PayrollRun $payroll_run)
    {
        $payroll_run->delete();

        return $this->success(null, 'Deleted');
    }
}

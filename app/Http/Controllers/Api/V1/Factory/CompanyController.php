<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Company;
use Illuminate\Http\Request;

class CompanyController extends BaseController
{
    public function index(Request $request)
    {
        $query = Company::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Company);
        $company = Company::create($validated);

        return $this->success($company, 'Created', 201);
    }

    public function show(Company $company)
    {
        return $this->success($company);
    }

    public function update(Request $request, Company $company)
    {
        $validated = $this->validatedFor($request, $company, $company->id);
        $company->update($validated);

        return $this->success($company, 'Updated');
    }

    public function destroy(Company $company)
    {
        $company->delete();

        return $this->success(null, 'Deleted');
    }
}

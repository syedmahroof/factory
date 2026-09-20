<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends BaseController
{
    public function index(Request $request)
    {
        $query = Customer::query();
        $request->merge(['search_fields' => ['name', 'code']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Customer);
        $customer = Customer::create($validated);

        return $this->success($customer, 'Created', 201);
    }

    public function show(Customer $customer)
    {
        return $this->success($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $this->validatedFor($request, $customer, $customer->id);
        $customer->update($validated);

        return $this->success($customer, 'Updated');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return $this->success(null, 'Deleted');
    }
}

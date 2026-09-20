<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Role;
use Illuminate\Http\Request;

class RoleController extends BaseController
{
    public function index(Request $request)
    {
        $query = Role::query();
        $request->merge(['search_fields' => ['name']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Role);
        $role = Role::create($validated);

        return $this->success($role, 'Created', 201);
    }

    public function show(Role $role)
    {
        return $this->success($role);
    }

    public function update(Request $request, Role $role)
    {
        $validated = $this->validatedFor($request, $role, $role->id);
        $role->update($validated);

        return $this->success($role, 'Updated');
    }

    public function destroy(Role $role)
    {
        $role->delete();

        return $this->success(null, 'Deleted');
    }
}

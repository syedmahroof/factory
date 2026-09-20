<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\User;
use Illuminate\Http\Request;

class UserController extends BaseController
{
    public function index(Request $request)
    {
        $query = User::query();
        $request->merge(['search_fields' => ['name', 'email']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new User);
        $user = User::create($validated);

        return $this->success($user, 'Created', 201);
    }

    public function show(User $user)
    {
        return $this->success($user);
    }

    public function update(Request $request, User $user)
    {
        $validated = $this->validatedFor($request, $user, $user->id);
        $user->update($validated);

        return $this->success($user, 'Updated');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return $this->success(null, 'Deleted');
    }
}

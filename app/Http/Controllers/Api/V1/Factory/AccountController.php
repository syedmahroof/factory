<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends BaseController
{
    public function index(Request $request)
    {
        $query = Account::query();
        $request->merge(['search_fields' => ['code', 'name']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Account);
        $account = Account::create($validated);

        return $this->success($account, 'Created', 201);
    }

    public function show(Account $account)
    {
        return $this->success($account);
    }

    public function update(Request $request, Account $account)
    {
        $validated = $this->validatedFor($request, $account, $account->id);
        $account->update($validated);

        return $this->success($account, 'Updated');
    }

    public function destroy(Account $account)
    {
        $account->delete();

        return $this->success(null, 'Deleted');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\BankAccount;
use Illuminate\Http\Request;

class BankAccountController extends BaseController
{
    public function index(Request $request)
    {
        $query = BankAccount::query();
        $request->merge(['search_fields' => ['name', 'account_number']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new BankAccount);
        $bank_account = BankAccount::create($validated);

        return $this->success($bank_account, 'Created', 201);
    }

    public function show(BankAccount $bank_account)
    {
        return $this->success($bank_account);
    }

    public function update(Request $request, BankAccount $bank_account)
    {
        $validated = $this->validatedFor($request, $bank_account, $bank_account->id);
        $bank_account->update($validated);

        return $this->success($bank_account, 'Updated');
    }

    public function destroy(BankAccount $bank_account)
    {
        $bank_account->delete();

        return $this->success(null, 'Deleted');
    }
}

<?php

namespace App\Http\Requests\Account;

use App\Models\User;

class ExportAccountsRequest extends IndexAccountsRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('export', User::class);
    }
}

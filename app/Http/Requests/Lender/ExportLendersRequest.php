<?php

namespace App\Http\Requests\Lender;

use App\Models\User;

class ExportLendersRequest extends IndexLendersRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('export', User::class);
    }
}

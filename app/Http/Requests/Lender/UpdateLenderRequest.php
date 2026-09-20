<?php

namespace App\Http\Requests\Lender;

use App\Models\User;

/**
 * Same shape as the create form — only the record being written differs, and
 * that lives on the route.
 */
class UpdateLenderRequest extends StoreLenderRequest
{
    use ResolvesLender;

    public function authorize(): bool
    {
        return $this->user()->can('update', $this->lender());
    }

    protected function existing(): ?User
    {
        return $this->lender();
    }
}

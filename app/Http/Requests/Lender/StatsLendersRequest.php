<?php

namespace App\Http\Requests\Lender;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StatsLendersRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('viewAny', User::class);
    }

    public function rules(): array
    {
        return [];
    }
}

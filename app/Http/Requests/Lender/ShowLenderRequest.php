<?php

namespace App\Http\Requests\Lender;

use Illuminate\Foundation\Http\FormRequest;

class ShowLenderRequest extends FormRequest
{
    use ResolvesLender;

    public function authorize(): bool
    {
        return $this->user()->can('view', $this->lender());
    }

    public function rules(): array
    {
        return [];
    }
}

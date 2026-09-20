<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveHolidayRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            // Unique on the date: Terms::GenerateDates() skips a date because it is in
            // this table, so a duplicate would be silently harmless but is still wrong.
            'date' => ['required', 'date', Rule::unique('holidays', 'date')->ignore($this->route('holiday'))],
            'name' => ['required', 'string', 'max:191'],
        ];
    }
}

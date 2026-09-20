<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SaveVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:191'],
            'video_url' => ['required', 'string', 'max:2048', 'url'],
        ];
    }

    public function attributes(): array
    {
        return ['video_url' => 'video URL'];
    }
}

<?php

declare(strict_types=1);

namespace App\Http\Requests\Settings;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class SaveGeneralSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    public function rules(): array
    {
        return [
            'admin_email' => ['nullable', 'email', 'max:191'],
            'system_admin' => ['nullable', 'string', 'max:191'],
            /*
             * The three caps the allocation screens read. Percentages are bounded
             * because a share over 100 would let one investor be assigned more of an
             * advance than exists, and the split has no way to refuse it later.
             */
            'max_assign_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_investment_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'minimum_investment_value' => ['nullable', 'numeric', 'min:0'],
            /*
             * The agent's cut of every payment, taken before the management fee.
             * Platform-wide, so a mistyped value here mis-splits every advance —
             * bounded to the 0-30 the merchant form used to offer.
             */
            'agent_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:30'],
        ];
    }

    public function attributes(): array
    {
        return [
            'admin_email' => 'admin e-mail',
            'system_admin' => 'system admin',
            'max_assign_percentage' => 'maximum assignment %',
            'max_investment_percentage' => 'maximum investment %',
            'minimum_investment_value' => 'minimum investment',
            'agent_fee_percentage' => 'agent fee %',
        ];
    }
}

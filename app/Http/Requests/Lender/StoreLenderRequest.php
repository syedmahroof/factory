<?php

namespace App\Http\Requests\Lender;

use App\Models\Lender;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreLenderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('create', User::class);
    }

    /**
     * The lender being written, or null when creating — the update subclass
     * overrides this so the unique rules can ignore the record's own row.
     */
    protected function existing(): ?User
    {
        return null;
    }

    /**
     * The form posts unselected dropdowns and blank numbers as '', which passes
     * `nullable` and then reaches the column as an empty string — a FK error on
     * company_id and a silent 0 elsewhere.
     */
    protected function prepareForValidation(): void
    {
        $blankToNull = [
            'company_id', 'status_id', 'syndication_fee_type', 'lag_time_days',
            'management_fee_percentage', 'up_sell_management_fee_percentage',
            'underwriting_fee_percentage', 'syndication_fee_percentage',
            'up_sell_syndication_fee_percentage',
        ];

        $this->merge(
            collect($blankToNull)
                ->filter(fn ($key) => $this->has($key) && $this->input($key) === '')
                ->mapWithKeys(fn ($key) => [$key => null])
                ->all()
        );
    }

    public function rules(): array
    {
        $profileId = $this->existing()?->Lender?->id;

        return [
            'name' => ['required', 'string', 'max:255'],
            'username' => [
                'nullable', 'string', 'max:255',
                Rule::unique('lenders', 'username')->ignore($profileId),
            ],
            'email' => [
                'required', 'string', 'email', 'max:255',
                Rule::unique('users', 'email')->ignore($this->existing()?->id),
            ],
            'notification_email' => ['nullable', 'string', 'email', 'max:255'],
            'cell_phone' => ['nullable', 'string', 'max:50'],
            'company_id' => [
                'nullable', 'integer',
                Rule::exists('users', 'id')->where('user_type_id', UserType::Company),
            ],

            'management_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'up_sell_management_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'underwriting_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'syndication_fee_type' => ['required', 'integer', Rule::in(array_keys(Lender::syndicationFeeTypeOptions()))],
            'syndication_fee_percentage' => [
                'nullable',
                Rule::requiredIf(fn () => (int) $this->input('syndication_fee_type') !== Lender::SyndicationFeeNone),
                'numeric', 'min:0', 'max:100',
            ],
            'up_sell_syndication_fee_percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'lag_time_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'ip_filtering' => ['nullable', 'boolean'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
            'password_confirmation' => ['nullable', 'string'],
            'status_id' => ['nullable', 'integer', Rule::in(array_keys(User::statusOptions()))],
        ];
    }

    public function messages(): array
    {
        return [
            'username.unique' => 'That username is already taken by another lender.',
            'email.unique' => 'That email address is already in use by another account.',
            'company_id.exists' => 'Pick a company from the list.',
            'syndication_fee_percentage.required' => 'Enter the syndication fee % for the type you picked.',
        ];
    }

    public function attributes(): array
    {
        return [
            'management_fee_percentage' => 'management fee %',
            'up_sell_management_fee_percentage' => 'upsell management fee %',
            'underwriting_fee_percentage' => 'underwriting fee %',
            'syndication_fee_type' => 'syndication fee type',
            'syndication_fee_percentage' => 'syndication fee %',
            'up_sell_syndication_fee_percentage' => 'upsell syndication fee %',
            'lag_time_days' => 'lag time',
            'ip_filtering' => 'IP filtering',
            'company_id' => 'company',
            'notification_email' => 'notification e-mail',
        ];
    }
}

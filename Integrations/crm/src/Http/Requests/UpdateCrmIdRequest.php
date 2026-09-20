<?php

namespace Integrations\Crm\Http\Requests;

use App\Services\ApiLog\Enums\ApiServiceName;
use App\Services\ApiLog\Facades\ApiLogger;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\ValidationException;

class UpdateCrmIdRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        ApiLogger::record(ApiServiceName::CrmUpdateCrmId, $this);
    }

    public function rules(): array
    {
        return [
            'merchant_id' => ['required', 'integer'],
            'crm_id' => ['nullable', 'integer'],
            'payment_status' => ['nullable', 'string', 'max:255'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        $exception = new ValidationException($validator);

        throw new HttpResponseException(response()->json([
            'status' => 0,
            'message' => $exception->getMessage(),
            'errors' => $validator->errors(),
        ], $exception->status));
    }
}

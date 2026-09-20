<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreBankAccountRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['account_id' => 'required|exists:accounts,id', 'bank_name' => 'required|string|max:255', 'account_number' => 'required|string|max:50', 'routing_number' => 'nullable|string|max:20', 'currency' => 'nullable|string|max:10'];
    }
}
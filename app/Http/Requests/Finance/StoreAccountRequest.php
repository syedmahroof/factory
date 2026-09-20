<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreAccountRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'code' => 'required|string|max:20|unique:accounts,code',
            'name' => 'required|string|max:255',
            'account_group_id' => 'required|exists:account_groups,id',
            'type' => 'required|in:asset,liability,equity,revenue,expense',
            'sub_type' => 'nullable|string|max:50',
            'parent_id' => 'nullable|exists:accounts,id',
            'cost_center_id' => 'nullable|exists:cost_centers,id',
            'description' => 'nullable|string',
            'is_bank_account' => 'boolean',
            'is_cash_account' => 'boolean',
            'opening_balance' => 'nullable|numeric',
            'is_active' => 'boolean',
        ];
    }
}
<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreFixedAssetRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['name' => 'required|string|max:255', 'account_id' => 'required|exists:accounts,id', 'depreciation_account_id' => 'nullable|exists:accounts,id', 'acquisition_cost' => 'required|numeric|min:0', 'salvage_value' => 'nullable|numeric|min:0', 'useful_life_months' => 'required|integer|min:1', 'depreciation_method' => 'required|in:straight_line,declining_balance,sum_of_years,units_of_production', 'acquisition_date' => 'required|date'];
    }
}
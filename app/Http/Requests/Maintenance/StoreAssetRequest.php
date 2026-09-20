<?php
namespace App\Http\Requests\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
class StoreAssetRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'plant_id' => 'required|exists:plants,id',
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:assets,code',
            'category' => 'required|string|max:100',
            'location' => 'nullable|string|max:255',
            'manufacturer' => 'nullable|string|max:255',
            'model' => 'nullable|string|max:255',
            'serial_number' => 'nullable|string|max:100',
            'purchase_date' => 'nullable|date',
            'purchase_cost' => 'nullable|numeric|min:0',
            'useful_life_years' => 'nullable|integer|min:1',
            'salvage_value' => 'nullable|numeric|min:0',
            'warranty_expiry' => 'nullable|date',
            'criticality' => 'required|in:low,medium,high,critical',
            'status' => 'required|in:active,inactive,retired',
        ];
    }
}
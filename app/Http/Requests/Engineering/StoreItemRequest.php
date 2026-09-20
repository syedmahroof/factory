<?php
namespace App\Http\Requests\Engineering;
use Illuminate\Foundation\Http\FormRequest;
class StoreItemRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'code' => 'required|string|max:50|unique:items,code',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:raw,semi_finished,finished,consumable,service',
            'category_id' => 'required|exists:item_categories,id',
            'base_uom_id' => 'required|exists:uoms,id',
            'standard_cost' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'purchase_price' => 'nullable|numeric|min:0',
            'min_stock' => 'nullable|numeric|min:0',
            'max_stock' => 'nullable|numeric|min:0',
            'reorder_point' => 'nullable|numeric|min:0',
            'lead_time_days' => 'nullable|integer|min:0',
            'weight' => 'nullable|numeric|min:0',
            'volume' => 'nullable|numeric|min:0',
            'is_active' => 'boolean',
            'is_batch_tracked' => 'boolean',
            'is_serial_tracked' => 'boolean',
        ];
    }
}
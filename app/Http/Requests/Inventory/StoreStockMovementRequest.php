<?php
namespace App\Http\Requests\Inventory;
use Illuminate\Foundation\Http\FormRequest;
class StoreStockMovementRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'required|exists:items,id',
            'movement_type' => 'required|in:receipt,issue,transfer,adjustment,return,scrap',
            'from_warehouse_id' => 'nullable|exists:warehouses,id',
            'to_warehouse_id' => 'nullable|exists:warehouses,id',
            'quantity' => 'required|numeric|min:0.001',
            'uom_id' => 'required|exists:uoms,id',
            'batch_number' => 'nullable|string|max:50',
            'serial_number' => 'nullable|string|max:50',
            'reason' => 'nullable|string|max:255',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ];
    }
}
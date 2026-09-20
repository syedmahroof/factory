<?php
namespace App\Http\Requests\Production;
use Illuminate\Foundation\Http\FormRequest;
class StoreProductionOrderRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'required|exists:items,id',
            'bom_id' => 'nullable|exists:boms,id',
            'routing_id' => 'nullable|exists:routings,id',
            'type' => 'required|in:standard,repetitive,project,maintenance',
            'planned_quantity' => 'required|numeric|min:0.001',
            'uom_id' => 'required|exists:uoms,id',
            'planned_start_date' => 'required|date',
            'planned_end_date' => 'required|date|after_or_equal:planned_start_date',
            'priority' => 'required|in:low,medium,high,urgent',
            'warehouse_id' => 'nullable|exists:warehouses,id',
            'notes' => 'nullable|string',
        ];
    }
}
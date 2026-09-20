<?php
namespace App\Http\Requests\Procurement;
use Illuminate\Foundation\Http\FormRequest;
class StorePurchaseOrderRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'supplier_id' => 'required|exists:suppliers,id',
            'requisition_id' => 'nullable|exists:purchase_requisitions,id',
            'plant_id' => 'required|exists:plants,id',
            'order_date' => 'required|date',
            'expected_date' => 'required|date|after_or_equal:order_date',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_address' => 'nullable|string',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.uom_id' => 'required|exists:uoms,id',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
            'lines.*.required_date' => 'required|date',
        ];
    }
}
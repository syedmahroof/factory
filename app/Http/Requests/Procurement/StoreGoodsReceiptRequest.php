<?php
namespace App\Http\Requests\Procurement;
use Illuminate\Foundation\Http\FormRequest;
class StoreGoodsReceiptRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'purchase_order_id' => 'required|exists:purchase_orders,id',
            'received_by' => 'required|exists:users,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'received_date' => 'required|date',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.purchase_order_line_id' => 'required|exists:purchase_order_lines,id',
            'lines.*.received_quantity' => 'required|numeric|min:0.001',
            'lines.*.accepted_quantity' => 'required|numeric|min:0',
            'lines.*.rejected_quantity' => 'nullable|numeric|min:0',
            'lines.*.batch_number' => 'nullable|string|max:50',
            'lines.*.expiry_date' => 'nullable|date|after:today',
            'lines.*.storage_location' => 'nullable|string|max:100',
        ];
    }
}
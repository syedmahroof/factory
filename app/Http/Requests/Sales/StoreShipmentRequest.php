<?php
namespace App\Http\Requests\Sales;
use Illuminate\Foundation\Http\FormRequest;
class StoreShipmentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'sales_order_id' => 'required|exists:sales_orders,id',
            'warehouse_id' => 'required|exists:warehouses,id',
            'shipped_by' => 'required|exists:users,id',
            'shipped_date' => 'required|date',
            'carrier' => 'nullable|string|max:255',
            'tracking_number' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.sales_order_line_id' => 'required|exists:sales_order_lines,id',
            'lines.*.shipped_quantity' => 'required|numeric|min:0.001',
        ];
    }
}
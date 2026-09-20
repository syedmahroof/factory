<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreSupplierInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['supplier_id' => 'required|exists:suppliers,id', 'purchase_order_id' => 'nullable|exists:purchase_orders,id', 'invoice_date' => 'required|date', 'due_date' => 'required|date|after_or_equal:invoice_date', 'total_amount' => 'required|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0|max:100'];
    }
}
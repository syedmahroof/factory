<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreCustomerInvoiceRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['customer_id' => 'required|exists:customers,id', 'sales_order_id' => 'nullable|exists:sales_orders,id', 'invoice_date' => 'required|date', 'due_date' => 'required|date|after_or_equal:invoice_date', 'total_amount' => 'required|numeric|min:0', 'tax_rate' => 'nullable|numeric|min:0|max:100'];
    }
}
<?php
namespace App\Http\Requests\Sales;
use Illuminate\Foundation\Http\FormRequest;
class StoreQuotationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'customer_id' => 'required|exists:customers,id',
            'quotation_date' => 'required|date',
            'valid_until' => 'required|date|after_or_equal:quotation_date',
            'payment_terms' => 'nullable|string|max:100',
            'delivery_terms' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.uom_id' => 'required|exists:uoms,id',
            'lines.*.unit_price' => 'required|numeric|min:0',
            'lines.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'lines.*.tax_rate' => 'nullable|numeric|min:0|max:100',
        ];
    }
}
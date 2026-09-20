<?php
namespace App\Http\Requests\Sales;
use Illuminate\Foundation\Http\FormRequest;
class StoreRmaRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['customer_id' => 'required|exists:customers,id', 'rma_date' => 'required|date', 'reason' => 'required|string', 'lines' => 'required|array|min:1', 'lines.*.item_id' => 'required|exists:items,id', 'lines.*.quantity' => 'required|numeric|min:0.001', 'lines.*.uom_id' => 'required|exists:uoms,id'];
    }
}
<?php
namespace App\Http\Requests\Procurement;
use Illuminate\Foundation\Http\FormRequest;
class StoreLandedCostRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['goods_receipt_id' => 'required|exists:goods_receipts,id', 'freight' => 'nullable|numeric|min:0', 'duty' => 'nullable|numeric|min:0', 'insurance' => 'nullable|numeric|min:0', 'other_charges' => 'nullable|numeric|min:0', 'allocation_method' => 'required|in:value,quantity,weight,volume'];
    }
}
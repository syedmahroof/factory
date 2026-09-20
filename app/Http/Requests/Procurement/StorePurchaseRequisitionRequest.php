<?php
namespace App\Http\Requests\Procurement;
use Illuminate\Foundation\Http\FormRequest;
class StorePurchaseRequisitionRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'requested_by' => 'required|exists:users,id',
            'department_id' => 'required|exists:departments,id',
            'priority' => 'required|in:low,medium,high,urgent',
            'needed_by_date' => 'required|date|after:today',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.uom_id' => 'required|exists:uoms,id',
            'lines.*.estimated_price' => 'nullable|numeric|min:0',
            'lines.*.required_date' => 'required|date',
        ];
    }
}
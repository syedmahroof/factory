<?php
namespace App\Http\Requests\Procurement;
use Illuminate\Foundation\Http\FormRequest;
class StoreRfqRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['created_by' => 'required|exists:users,id', 'issue_date' => 'required|date', 'response_deadline' => 'required|date|after_or_equal:issue_date', 'lines' => 'required|array|min:1', 'lines.*.item_id' => 'required|exists:items,id', 'lines.*.quantity' => 'required|numeric|min:0.001', 'lines.*.uom_id' => 'required|exists:uoms,id'];
    }
}
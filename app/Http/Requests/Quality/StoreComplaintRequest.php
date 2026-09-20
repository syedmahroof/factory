<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreComplaintRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['customer_id' => 'nullable|exists:customers,id', 'item_id' => 'nullable|exists:items,id', 'description' => 'required|string', 'severity' => 'required|in:low,medium,high,critical'];
    }
}
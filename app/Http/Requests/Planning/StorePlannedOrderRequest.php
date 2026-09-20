<?php
namespace App\Http\Requests\Planning;
use Illuminate\Foundation\Http\FormRequest;
class StorePlannedOrderRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['item_id' => 'required|exists:items,id', 'plant_id' => 'required|exists:plants,id', 'planned_quantity' => 'required|numeric|min:0.001', 'required_date' => 'required|date', 'order_type' => 'required|in:purchase,production,transfer'];
    }
}
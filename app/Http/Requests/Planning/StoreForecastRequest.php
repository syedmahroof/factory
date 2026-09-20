<?php
namespace App\Http\Requests\Planning;
use Illuminate\Foundation\Http\FormRequest;
class StoreForecastRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['item_id' => 'required|exists:items,id', 'plant_id' => 'required|exists:plants,id', 'forecast_date' => 'required|date', 'quantity' => 'required|numeric|min:0.001', 'type' => 'required|in:forecast,firm_order,safety_stock,inter_plant'];
    }
}
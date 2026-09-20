<?php
namespace App\Http\Requests\Production;
use Illuminate\Foundation\Http\FormRequest;
class StoreOperationJobRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'production_order_id' => 'required|exists:production_orders,id',
            'operation_id' => 'required|exists:operations,id',
            'work_center_id' => 'required|exists:work_centers,id',
            'planned_quantity' => 'required|numeric|min:0.001',
            'sequence' => 'nullable|integer|min:0',
        ];
    }
}
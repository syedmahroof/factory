<?php
namespace App\Http\Requests\Engineering;
use Illuminate\Foundation\Http\FormRequest;
class StoreRoutingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'required|exists:items,id',
            'version' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_default' => 'boolean',
            'operations' => 'required|array|min:1',
            'operations.*.work_center_id' => 'required|exists:work_centers,id',
            'operations.*.operation_no' => 'required|string|max:10',
            'operations.*.description' => 'required|string|max:255',
            'operations.*.setup_time' => 'nullable|numeric|min:0',
            'operations.*.run_time' => 'nullable|numeric|min:0',
            'operations.*.cycle_time' => 'nullable|numeric|min:0',
        ];
    }
}
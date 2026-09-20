<?php
namespace App\Http\Requests\Engineering;
use Illuminate\Foundation\Http\FormRequest;
class StoreBomRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'required|exists:items,id',
            'version' => 'required|string|max:20',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'lines' => 'required|array|min:1',
            'lines.*.item_id' => 'required|exists:items,id',
            'lines.*.quantity' => 'required|numeric|min:0.001',
            'lines.*.uom_id' => 'required|exists:uoms,id',
            'lines.*.scrap_percentage' => 'nullable|numeric|min:0|max:100',
            'lines.*.sequence' => 'nullable|integer|min:0',
        ];
    }
}
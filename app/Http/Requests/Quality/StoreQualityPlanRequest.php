<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreQualityPlanRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'required|exists:items,id',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'inspection_type' => 'required|in:incoming,in_process,final,audit',
            'sample_size' => 'nullable|integer|min:1',
            'frequency' => 'nullable|in:every_batch,every_shift,every_day,weekly,monthly',
            'is_active' => 'boolean',
            'characteristics' => 'nullable|array',
            'characteristics.*.name' => 'required|string|max:255',
            'characteristics.*.type' => 'required|in:dimensional,visual,chemical,performance',
            'characteristics.*.min_value' => 'nullable|numeric',
            'characteristics.*.max_value' => 'nullable|numeric',
            'characteristics.*.target_value' => 'nullable|numeric',
        ];
    }
}
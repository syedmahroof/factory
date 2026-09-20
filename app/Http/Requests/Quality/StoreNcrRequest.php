<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreNcrRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'item_id' => 'nullable|exists:items,id',
            'reported_by' => 'required|exists:users,id',
            'severity' => 'required|in:minor,major,critical',
            'description' => 'required|string',
            'root_cause' => 'nullable|string',
            'containment_action' => 'nullable|string',
            'corrective_action' => 'nullable|string',
            'preventive_action' => 'nullable|string',
            'target_date' => 'nullable|date',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
        ];
    }
}
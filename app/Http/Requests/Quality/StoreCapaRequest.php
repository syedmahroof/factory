<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreCapaRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['ncr_id' => 'nullable|exists:ncrs,id', 'type' => 'required|in:corrective,preventive', 'root_cause' => 'required|string', 'action_description' => 'required|string', 'owner_id' => 'required|exists:users,id', 'due_date' => 'required|date|after:today'];
    }
}
<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class StoreSkillMatrixRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['employee_id' => 'required|exists:employees,id', 'skill_name' => 'required|string|max:255', 'level' => 'required|in:trainee,beginner,intermediate,advanced,expert', 'work_center_id' => 'nullable|exists:work_centers,id', 'certification_date' => 'nullable|date', 'certification_expiry' => 'nullable|date|after:certification_date'];
    }
}
<?php
namespace App\Http\Requests\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
class StoreMaintenanceOrderRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'asset_id' => 'required|exists:assets,id',
            'maintenance_plan_id' => 'nullable|exists:maintenance_plans,id',
            'type' => 'required|in:preventive,predictive,corrective,emergency',
            'priority' => 'required|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'assigned_to' => 'nullable|exists:users,id',
            'scheduled_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:scheduled_date',
        ];
    }
}
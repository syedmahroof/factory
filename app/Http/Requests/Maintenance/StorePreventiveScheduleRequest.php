<?php
namespace App\Http\Requests\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
class StorePreventiveScheduleRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['maintenance_plan_id' => 'required|exists:maintenance_plans,id', 'asset_id' => 'required|exists:assets,id', 'trigger_type' => 'required|in:calendar,meter,condition', 'interval_days' => 'nullable|integer|min:1', 'meter_threshold' => 'nullable|numeric|min:0', 'next_due_date' => 'required|date'];
    }
}
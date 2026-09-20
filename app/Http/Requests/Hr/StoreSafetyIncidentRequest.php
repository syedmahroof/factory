<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class StoreSafetyIncidentRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['type' => 'required|in:incident,near_miss,first_aid,lost_time', 'reported_by' => 'required|exists:users,id', 'incident_date' => 'required|date', 'description' => 'required|string'];
    }
}
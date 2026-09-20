<?php
namespace App\Http\Requests\Maintenance;
use Illuminate\Foundation\Http\FormRequest;
class StoreMeterReadingRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['asset_id' => 'required|exists:assets,id', 'meter_name' => 'required|string|max:255', 'reading_value' => 'required|numeric|min:0', 'reading_date' => 'required|date', 'source' => 'required|in:manual,api,iot'];
    }
}
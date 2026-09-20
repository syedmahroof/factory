<?php
namespace App\Http\Requests\Quality;
use Illuminate\Foundation\Http\FormRequest;
class StoreCalibrationRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['instrument_code' => 'required|max:50', 'instrument_name' => 'required|max:255', 'plant_id' => 'required|exists:plants,id', 'last_calibration_date' => 'required|date', 'next_calibration_date' => 'required|date|after:last_calibration_date'];
    }
}
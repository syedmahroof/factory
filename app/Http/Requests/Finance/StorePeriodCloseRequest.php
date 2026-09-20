<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StorePeriodCloseRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['fiscal_period_id' => 'required|exists:fiscal_periods,id', 'module' => 'required|string|max:50'];
    }
}
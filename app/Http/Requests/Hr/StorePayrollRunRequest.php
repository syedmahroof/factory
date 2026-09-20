<?php
namespace App\Http\Requests\Hr;
use Illuminate\Foundation\Http\FormRequest;
class StorePayrollRunRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return ['period' => 'required|string|max:20', 'prepared_by' => 'required|exists:users,id', 'payslips' => 'required|array|min:1', 'payslips.*.employee_id' => 'required|exists:employees,id', 'payslips.*.basic_salary' => 'required|numeric|min:0'];
    }
}
<?php
namespace App\Http\Requests\Finance;
use Illuminate\Foundation\Http\FormRequest;
class StoreJournalRequest extends FormRequest {
    public function authorize(): bool { return true; }
    public function rules(): array {
        return [
            'fiscal_period_id' => 'required|exists:fiscal_periods,id',
            'date' => 'required|date',
            'type' => 'required|in:general,adjustment,closing,reversal',
            'description' => 'required|string|max:255',
            'reference_type' => 'nullable|string|max:255',
            'reference_id' => 'nullable|integer',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|exists:accounts,id',
            'lines.*.debit' => 'nullable|numeric|min:0',
            'lines.*.credit' => 'nullable|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.cost_center_id' => 'nullable|exists:cost_centers,id',
        ];
    }
}
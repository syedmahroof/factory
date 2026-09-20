<?php

namespace App\Actions\Finance;

use App\Models\Payment;
use App\Services\AuditService;
use App\Services\NumberGenerator;
use Illuminate\Support\Facades\DB;

class RecordPayment
{
    public function execute(array $data): Payment
    {
        return DB::transaction(function () use ($data) {
            $data['payment_number'] = NumberGenerator::next('payment', 'PAY');
            $data['status'] = 'completed';

            $payment = Payment::create($data);

            // Update balance on invoice
            if (isset($data['payable_type']) && isset($data['payable_id'])) {
                $invoice = $data['payable_type']::find($data['payable_id']);
                if ($invoice) {
                    $invoice->decrement('balance_amount', $data['amount']);
                    if ($invoice->balance_amount <= 0) {
                        $invoice->update(['status' => 'paid']);
                    }
                }
            }

            AuditService::log('created', 'finance', $payment, null, $data);

            return $payment;
        });
    }
}

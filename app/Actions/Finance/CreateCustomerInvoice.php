<?php

namespace App\Actions\Finance;

use App\Models\CustomerInvoice;
use App\Services\AuditService;
use App\Services\NumberGenerator;

class CreateCustomerInvoice
{
    public function execute(array $data): CustomerInvoice
    {
        $data['invoice_number'] = NumberGenerator::next('customer_invoice', 'AR');
        $data['tax_amount'] = ($data['total_amount'] ?? 0) * ($data['tax_rate'] ?? 0) / 100;
        $data['net_amount'] = ($data['total_amount'] ?? 0) + $data['tax_amount'];
        $data['balance_amount'] = $data['net_amount'];
        $data['status'] = 'open';
        $invoice = CustomerInvoice::create($data);
        AuditService::log('created', 'finance', $invoice);

        return $invoice;
    }
}

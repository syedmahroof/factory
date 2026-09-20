<?php

namespace App\Actions\Finance;

use App\Models\SupplierInvoice;
use App\Services\AuditService;
use App\Services\NumberGenerator;

class CreateAccountsPayable
{
    public function execute(array $data): SupplierInvoice
    {
        $data['invoice_number'] = NumberGenerator::next('supplier_invoice', 'AP');
        $data['status'] = 'open';

        // Calculate amounts
        $data['tax_amount'] = ($data['total_amount'] ?? 0) * ($data['tax_rate'] ?? 0) / 100;
        $data['net_amount'] = ($data['total_amount'] ?? 0) + ($data['tax_amount'] ?? 0);
        $data['balance_amount'] = $data['net_amount'];

        $invoice = SupplierInvoice::create($data);
        AuditService::log('created', 'finance', $invoice, null, $data);

        return $invoice;
    }
}

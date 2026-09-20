<?php

namespace App\Actions\Finance;

use App\Models\SupplierInvoice;
use App\Services\AuditService;
use App\Services\NumberGenerator;

class CreateSupplierInvoice
{
    public function execute(array $data): SupplierInvoice
    {
        $data['invoice_number'] = NumberGenerator::next('supplier_invoice', 'AP');
        $data['tax_amount'] = ($data['total_amount'] ?? 0) * ($data['tax_rate'] ?? 0) / 100;
        $data['net_amount'] = ($data['total_amount'] ?? 0) + $data['tax_amount'];
        $data['balance_amount'] = $data['net_amount'];
        $data['status'] = 'open';
        $invoice = SupplierInvoice::create($data);
        AuditService::log('created', 'finance', $invoice);

        return $invoice;
    }
}

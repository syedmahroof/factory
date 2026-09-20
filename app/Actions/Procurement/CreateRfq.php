<?php
namespace App\Actions\Procurement;

use App\Models\{RequestForQuotation, RfqLine};
use App\Services\{NumberGenerator, AuditService};
use Illuminate\Support\Facades\DB;

class CreateRfq
{
    public function execute(array $data): RequestForQuotation
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $data['number'] = NumberGenerator::next('rfq', 'RFQ');
            $data['status'] = 'draft';
            $rfq = RequestForQuotation::create($data);
            foreach ($lines as $line) {
                $line['rfq_id'] = $rfq->id;
                RfqLine::create($line);
            }
            AuditService::log('created', 'procurement', $rfq);
            return $rfq;
        });
    }
}
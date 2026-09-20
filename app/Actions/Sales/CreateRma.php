<?php
namespace App\Actions\Sales;

use App\Models\{ReturnMerchandiseAuthorization, ReturnMerchandiseLine};
use App\Services\{NumberGenerator, AuditService};
use Illuminate\Support\Facades\DB;

class CreateRma
{
    public function execute(array $data): ReturnMerchandiseAuthorization
    {
        return DB::transaction(function () use ($data) {
            $lines = $data['lines'] ?? [];
            unset($data['lines']);
            $data['number'] = NumberGenerator::next('rma', 'RMA');
            $data['status'] = 'requested';
            $rma = ReturnMerchandiseAuthorization::create($data);
            foreach ($lines as $line) {
                $line['rma_id'] = $rma->id;
                ReturnMerchandiseLine::create($line);
            }
            AuditService::log('created', 'sales', $rma);
            return $rma;
        });
    }
}
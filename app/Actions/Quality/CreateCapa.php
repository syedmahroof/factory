<?php
namespace App\Actions\Quality;

use App\Models\CapaAction;
use App\Services\{NumberGenerator, AuditService};

class CreateCapa
{
    public function execute(array $data): CapaAction
    {
        $data['number'] = NumberGenerator::next('capa', 'CAPA');
        $data['status'] = 'open';
        $capa = CapaAction::create($data);
        AuditService::log('created', 'quality', $capa);
        return $capa;
    }
}
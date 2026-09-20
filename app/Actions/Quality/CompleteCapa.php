<?php
namespace App\Actions\Quality;

use App\Models\CapaAction;
use App\Services\AuditService;

class CompleteCapa
{
    public function execute(CapaAction $capa, ?string $effectivenessResult = null): CapaAction
    {
        $old = $capa->toArray();
        if ($effectivenessResult) {
            $capa->update(['status' => 'effective', 'effectiveness_result' => $effectivenessResult, 'effectiveness_review_date' => now()]);
        } else {
            $capa->update(['status' => 'completed', 'completed_date' => now()]);
        }
        AuditService::log('completed', 'quality', $capa, $old);
        return $capa;
    }
}
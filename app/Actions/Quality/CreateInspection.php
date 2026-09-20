<?php
namespace App\Actions\Quality;

use App\Models\Inspection;
use App\Models\InspectionResult;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateInspection
{
    public function execute(array $data): Inspection
    {
        return DB::transaction(function () use ($data) {
            $results = $data['results'] ?? [];
            unset($data['results']);

            $total = count($results);
            $passed = collect($results)->where('is_pass', true)->count();
            $data['status'] = ($passed == $total) ? 'accepted' : 'rejected';
            $data['pass_rate'] = $total > 0 ? ($passed / $total) * 100 : 0;

            $inspection = Inspection::create($data);

            foreach ($results as $result) {
                $result['inspection_id'] = $inspection->id;
                InspectionResult::create($result);
            }

            AuditService::log('created', 'quality', $inspection, null, $data);
            return $inspection;
        });
    }
}
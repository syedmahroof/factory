<?php
namespace App\Actions\Engineering;

use App\Models\Routing;
use App\Models\Operation;
use App\Services\AuditService;
use Illuminate\Support\Facades\DB;

class CreateRouting
{
    public function execute(array $data): Routing
    {
        return DB::transaction(function () use ($data) {
            $operations = $data['operations'] ?? [];
            unset($data['operations']);

            $routing = Routing::create($data);

            foreach ($operations as $op) {
                $op['routing_id'] = $routing->id;
                Operation::create($op);
            }

            AuditService::log('created', 'engineering', $routing, null, $data);
            return $routing;
        });
    }
}
<?php
namespace App\Actions\Organization;

use App\Models\Plant;
use App\Services\AuditService;

class CreatePlant
{
    public function execute(array $data): Plant
    {
        $plant = Plant::create($data);
        AuditService::log('created', 'organization', $plant, null, $data);
        return $plant;
    }
}
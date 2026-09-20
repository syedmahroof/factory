<?php
namespace App\Actions\Organization;

use App\Models\Warehouse;
use App\Services\AuditService;

class CreateWarehouse
{
    public function execute(array $data): Warehouse
    {
        $warehouse = Warehouse::create($data);
        AuditService::log('created', 'organization', $warehouse, null, $data);
        return $warehouse;
    }
}
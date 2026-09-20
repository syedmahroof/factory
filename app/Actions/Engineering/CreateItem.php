<?php
namespace App\Actions\Engineering;

use App\Models\Item;
use App\Services\AuditService;

class CreateItem
{
    public function execute(array $data): Item
    {
        $item = Item::create($data);
        AuditService::log('created', 'engineering', $item, null, $data);
        return $item;
    }
}
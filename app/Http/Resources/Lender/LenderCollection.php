<?php

namespace App\Http\Resources\Lender;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * A page of the Lenders table. The paging figures are spelled out because the
 * collection is nested inside the { success, message, data } envelope, where
 * Laravel's own data/links/meta wrapper does not survive.
 */
class LenderCollection extends ResourceCollection
{
    public $collects = LenderResource::class;

    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
            'meta' => [
                'current_page' => $this->currentPage(),
                'last_page' => $this->lastPage(),
                'per_page' => $this->perPage(),
                'total' => $this->total(),
                'from' => $this->firstItem(),
                'to' => $this->lastItem(),
            ],
        ];
    }
}

<?php

namespace App\Http\Resources\Account;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * A page of the Accounts table.
 *
 * Rows are shaped by AccountResource; the paging figures are spelled out here so
 * the payload keeps them wherever it ends up — Laravel's own data/links/meta
 * envelope only survives when a collection *is* the response, and this one is
 * nested inside the { success, message, data } wrapper.
 */
class AccountCollection extends ResourceCollection
{
    public $collects = AccountResource::class;

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

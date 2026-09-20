<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Item;
use Illuminate\Http\Request;

class ItemController extends BaseController
{
    public function index(Request $request)
    {
        $query = Item::query();
        $request->merge(['search_fields' => ['name', 'code', 'type']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Item);
        $item = Item::create($validated);

        return $this->success($item, 'Created', 201);
    }

    public function show(Item $item)
    {
        return $this->success($item);
    }

    public function update(Request $request, Item $item)
    {
        $validated = $this->validatedFor($request, $item, $item->id);
        $item->update($validated);

        return $this->success($item, 'Updated');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return $this->success(null, 'Deleted');
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Asset;
use Illuminate\Http\Request;

class AssetController extends BaseController
{
    public function index(Request $request)
    {
        $query = Asset::query();
        $request->merge(['search_fields' => ['asset_number', 'name']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Asset);
        $asset = Asset::create($validated);

        return $this->success($asset, 'Created', 201);
    }

    public function show(Asset $asset)
    {
        return $this->success($asset);
    }

    public function update(Request $request, Asset $asset)
    {
        $validated = $this->validatedFor($request, $asset, $asset->id);
        $asset->update($validated);

        return $this->success($asset, 'Updated');
    }

    public function destroy(Asset $asset)
    {
        $asset->delete();

        return $this->success(null, 'Deleted');
    }
}

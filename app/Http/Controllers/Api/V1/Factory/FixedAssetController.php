<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\FixedAsset;
use Illuminate\Http\Request;

class FixedAssetController extends BaseController
{
    public function index(Request $request)
    {
        $query = FixedAsset::query();
        $request->merge(['search_fields' => ['asset_number', 'name']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new FixedAsset);
        $fixed_asset = FixedAsset::create($validated);

        return $this->success($fixed_asset, 'Created', 201);
    }

    public function show(FixedAsset $fixed_asset)
    {
        return $this->success($fixed_asset);
    }

    public function update(Request $request, FixedAsset $fixed_asset)
    {
        $validated = $this->validatedFor($request, $fixed_asset, $fixed_asset->id);
        $fixed_asset->update($validated);

        return $this->success($fixed_asset, 'Updated');
    }

    public function destroy(FixedAsset $fixed_asset)
    {
        $fixed_asset->delete();

        return $this->success(null, 'Deleted');
    }
}

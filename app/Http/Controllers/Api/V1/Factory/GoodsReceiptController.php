<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\GoodsReceipt;
use Illuminate\Http\Request;

class GoodsReceiptController extends BaseController
{
    public function index(Request $request)
    {
        $query = GoodsReceipt::query();
        $request->merge(['search_fields' => ['number', 'status']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new GoodsReceipt);
        $goods_receipt = GoodsReceipt::create($validated);

        return $this->success($goods_receipt, 'Created', 201);
    }

    public function show(GoodsReceipt $goods_receipt)
    {
        return $this->success($goods_receipt);
    }

    public function update(Request $request, GoodsReceipt $goods_receipt)
    {
        $validated = $this->validatedFor($request, $goods_receipt, $goods_receipt->id);
        $goods_receipt->update($validated);

        return $this->success($goods_receipt, 'Updated');
    }

    public function destroy(GoodsReceipt $goods_receipt)
    {
        $goods_receipt->delete();

        return $this->success(null, 'Deleted');
    }
}

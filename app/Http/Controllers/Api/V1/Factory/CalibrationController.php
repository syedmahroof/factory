<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\Calibration;
use Illuminate\Http\Request;

class CalibrationController extends BaseController
{
    public function index(Request $request)
    {
        $query = Calibration::query();
        $request->merge(['search_fields' => ['instrument_name', 'asset_number']]);
        $this->applyScopes($query, $request);

        return $this->success($this->paginated($query, $request));
    }

    public function store(Request $request)
    {
        $validated = $this->validatedFor($request, new Calibration);
        $calibration = Calibration::create($validated);

        return $this->success($calibration, 'Created', 201);
    }

    public function show(Calibration $calibration)
    {
        return $this->success($calibration);
    }

    public function update(Request $request, Calibration $calibration)
    {
        $validated = $this->validatedFor($request, $calibration, $calibration->id);
        $calibration->update($validated);

        return $this->success($calibration, 'Updated');
    }

    public function destroy(Calibration $calibration)
    {
        $calibration->delete();

        return $this->success(null, 'Deleted');
    }
}

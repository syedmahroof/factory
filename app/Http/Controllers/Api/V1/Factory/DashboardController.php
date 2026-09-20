<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Actions\Dashboard\BuildFactoryActivityAction;
use App\Actions\Dashboard\ResolveReportPeriodAction;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DashboardController extends BaseController
{
    public function index(Request $request, BuildFactoryActivityAction $activity)
    {
        $filters = $request->validate([
            'period' => ['sometimes', Rule::in([...array_keys(ResolveReportPeriodAction::presets()), 'custom'])],
            'granularity' => ['sometimes', Rule::in(ResolveReportPeriodAction::GRANULARITIES)],
            'from_date' => ['sometimes', 'nullable', 'date'],
            'to_date' => ['sometimes', 'nullable', 'date'],
        ]);

        return $this->success($activity->execute(
            $filters['period'] ?? '30d',
            $filters['granularity'] ?? 'auto',
            $filters['from_date'] ?? null,
            $filters['to_date'] ?? null,
        ));
    }
}

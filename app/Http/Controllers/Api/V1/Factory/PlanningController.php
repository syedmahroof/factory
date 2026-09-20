<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Actions\Planning\ConvertPlannedOrder;
use App\Actions\Planning\RunMrp;
use App\Models\MrpRun;
use App\Models\PlannedOrder;
use Illuminate\Http\Request;

class PlanningController extends BaseController
{
    public function index(Request $request)
    {
        return $this->success([
            'runs' => MrpRun::latest()->limit(20)->get(),
            'planned_orders' => $this->paginated(PlannedOrder::query(), $request, ['item']),
        ]);
    }

    public function runMrp(Request $request)
    {
        $result = app(RunMrp::class)->execute($request->user()->company_id);

        return $this->success($result, 'MRP run completed');
    }

    public function convertPlannedOrder(Request $request, PlannedOrder $plannedOrder)
    {
        $result = app(ConvertPlannedOrder::class)->execute($plannedOrder);

        return $this->success($result, 'Planned order converted');
    }
}

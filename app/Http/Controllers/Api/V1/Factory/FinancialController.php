<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Actions\Finance\ClosePeriod;
use App\Actions\Finance\GenerateBalanceSheet;
use App\Actions\Finance\GenerateProfitLoss;
use App\Actions\Finance\GenerateTrialBalance;
use App\Models\PeriodClose;
use Illuminate\Http\Request;

class FinancialController extends BaseController
{
    public function trialBalance(Request $request)
    {
        $data = app(GenerateTrialBalance::class)->execute(
            $request->user()->company_id,
            $request->get('from', now()->startOfYear()->format('Y-m-d')),
            $request->get('to', now()->format('Y-m-d'))
        );

        return $this->success($data);
    }

    public function balanceSheet(Request $request)
    {
        return $this->success(app(GenerateBalanceSheet::class)->execute($request->user()->company_id));
    }

    public function profitLoss(Request $request)
    {
        return $this->success(app(GenerateProfitLoss::class)->execute(
            $request->user()->company_id,
            $request->get('from', now()->startOfYear()->format('Y-m-d')),
            $request->get('to', now()->format('Y-m-d'))
        ));
    }

    public function periodClose(Request $request)
    {
        $validated = $request->validate(['period' => 'required|string']);
        $result = app(ClosePeriod::class)->execute($request->user()->company_id, $validated['period'], $request->user()->id);

        return $this->success($result, 'Period closed');
    }

    public function periodList()
    {
        return $this->success(PeriodClose::latest()->limit(24)->get());
    }
}

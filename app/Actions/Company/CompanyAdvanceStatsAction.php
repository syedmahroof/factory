<?php

namespace App\Actions\Company;

use App\Actions\ActionResult;
use App\Models\User;
use App\Services\Company\CompanyService;
use Throwable;

class CompanyAdvanceStatsAction
{
    public function handle(User $company, array $filters): ActionResult
    {
        try {
            $row = (new CompanyService)->advances($company, $filters)
                ->selectRaw('SUM(merchant_investors.funded) as funded')
                ->selectRaw('SUM(merchant_investors.rtr) as rtr')
                ->selectRaw('SUM(merchant_investors.invested) as invested')
                ->selectRaw('SUM(merchant_investors.investment_fee_amount) as investment_fee_amount')
                ->selectRaw('SUM(merchant_investors.paid_amount) as paid_amount')
                ->selectRaw('SUM(merchant_investors.share) as share')
                ->selectRaw('SUM(merchant_investors.paid_management_fee) as paid_management_fee')
                ->selectRaw('SUM(merchant_investors.paid_agent_fee) as paid_agent_fee')
                ->selectRaw('SUM(CASE WHEN (merchant_investors.invested-merchant_investors.paid_net_amount) >= 0 THEN merchant_investors.paid_net_amount ELSE merchant_investors.invested END) as principal')
                ->selectRaw('SUM(CASE WHEN (merchant_investors.paid_net_amount-merchant_investors.invested) >= 0 THEN (merchant_investors.paid_net_amount-merchant_investors.invested) ELSE 0 END) as profit')
                ->first();

            $rtr = (float) ($row->rtr ?? 0);
            $paidAmount = (float) ($row->paid_amount ?? 0);

            $data = [
                'funded' => (float) ($row->funded ?? 0),
                'rtr' => $rtr,
                'invested' => (float) ($row->invested ?? 0),
                'investment_fee_amount' => (float) ($row->investment_fee_amount ?? 0),
                'paid_amount' => $paidAmount,
                'balance' => $rtr - $paidAmount,
                'share' => (float) ($row->share ?? 0),
                'paid_management_fee' => (float) ($row->paid_management_fee ?? 0),
                'paid_agent_fee' => (float) ($row->paid_agent_fee ?? 0),
                'principal' => (float) ($row->principal ?? 0),
                'profit' => (float) ($row->profit ?? 0),
            ];

            return ActionResult::success($data);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load the totals.', 500);
        }
    }
}

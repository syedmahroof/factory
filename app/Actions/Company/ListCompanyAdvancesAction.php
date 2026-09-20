<?php

namespace App\Actions\Company;

use App\Actions\ActionResult;
use App\Http\Resources\Company\CompanyAdvanceCollection;
use App\Models\User;
use App\Services\Company\CompanyService;
use Throwable;

class ListCompanyAdvancesAction
{
    public function handle(User $company, array $filters, bool $paginate = true): ActionResult
    {
        try {
            $sort = $filters['sort'] ?? 'Merchant';
            $field = CompanyService::SORT_COLUMNS[$sort] ?? 'merchants_user.name';
            $direction = ($filters['direction'] ?? 'desc') === 'asc' ? 'asc' : 'desc';

            if ($sort === 'balance') {
                $direction = $direction === 'asc' ? 'desc' : 'asc';
            }

            $query = (new CompanyService)->advances($company, $filters)
                ->select([
                    'merchant_investors.id',
                    'merchant_investors.merchant_id',
                    'merchant_investors.investor_id',
                    'merchant_investors.company_id',
                    'merchant_investors.funded',
                    'merchant_investors.investment_fee_amount',
                    'merchant_investors.invested',
                    'merchant_investors.rtr',
                    'merchant_investors.completed_percentage',
                    'merchant_investors.management_fee_percentage',
                    'merchant_investors.paid_amount',
                    'merchant_investors.paid_net_amount',
                    'merchant_investors.paid_management_fee',
                    'merchant_investors.paid_agent_fee',
                    'merchant_investors.share',
                    'merchant_investors.active_status',
                    'merchant_investors.source',
                    'merchant_investors.created_at',
                    'users.name AS investor_name',
                    'merchants_user.name AS merchant_name',
                    'merchants.id AS merchant_row_id',
                ])
                ->selectRaw('merchant_investors.paid_amount/merchant_investors.rtr*100 as percentage')
                ->selectRaw('(CASE WHEN (merchant_investors.invested-merchant_investors.paid_net_amount) >= 0 THEN merchant_investors.paid_net_amount ELSE merchant_investors.invested END) AS principal')
                ->selectRaw('(CASE WHEN (merchant_investors.paid_net_amount-merchant_investors.invested) >= 0 THEN (merchant_investors.paid_net_amount-merchant_investors.invested) ELSE 0 END) AS profit')
                ->orderBy($field, $direction)
                ->orderByDesc('merchant_investors.id');

            if (! $paginate) {
                return ActionResult::success($query);
            }

            $advances = $query->paginate($filters['per_page'] ?? 15);

            return ActionResult::success(new CompanyAdvanceCollection($advances));
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load this company\'s advances.', 500);
        }
    }
}

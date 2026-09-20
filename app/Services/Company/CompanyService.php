<?php

declare(strict_types=1);

namespace App\Services\Company;

use App\Models\MerchantInvestor;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * The company module's shared queries.
 */
class CompanyService
{
    public const SORT_COLUMNS = [
        'id' => 'merchant_investors.id',
        'Merchant' => 'merchants_user.name',
        'Investor' => 'users.name',
        'funded' => 'merchant_investors.funded',
        'rtr' => 'merchant_investors.rtr',
        'invested' => 'merchant_investors.invested',
        'investment_fee_amount' => 'merchant_investors.investment_fee_amount',
        'paid_amount' => 'merchant_investors.paid_amount',
        'percentage' => 'percentage',
        'balance' => 'merchant_investors.paid_amount',
        'share' => 'merchant_investors.share',
        'paid_management_fee' => 'merchant_investors.paid_management_fee',
        'principal' => 'principal',
        'profit' => 'profit',
        'source' => 'merchant_investors.source',
        'active_status' => 'merchant_investors.active_status',
    ];

    /**
     * Columns the free-text term is matched against as a number,
     */
    private const NUMERIC_COLUMNS = [
        'funded',
        'rtr',
        'invested',
        'investment_fee_amount',
        'paid_amount',
        'paid_management_fee',
        'share',
    ];

    public function advances(User $company, array $filters): Builder
    {
        return MerchantInvestor::query()
            ->join('users', 'users.id', '=', 'merchant_investors.investor_id')
            ->leftJoin('users AS merchants_user', 'merchants_user.id', '=', 'merchant_investors.merchant_id')
            ->leftJoin('merchants', 'merchants.user_id', '=', 'merchant_investors.merchant_id')
            ->where('merchant_investors.company_id', $company->id)
            ->when($filters['search'] ?? null, fn ($q, $term) => $this->search($q, $term));
    }

    private function search(Builder $query, string $term): Builder
    {
        $like = '%'.$term.'%';
        $sources = array_keys(array_filter(
            MerchantInvestor::sourceOptions(),
            fn ($label) => stripos($label, $term) !== false
        ));
        $amount = str_replace([',', '$', ' '], '', $term);

        return $query->where(function ($q) use ($like, $amount, $sources) {
            $q->where('users.name', 'like', $like)
                ->orWhere('merchants_user.name', 'like', $like)
                ->orWhere('merchant_investors.source', 'like', $like)
                ->orWhere('merchant_investors.active_status', 'like', $like);

            if ($sources) {
                $q->orWhereIn('merchant_investors.source', $sources);
            }

            if (is_numeric($amount)) {
                foreach (self::NUMERIC_COLUMNS as $column) {
                    $q->orWhere('merchant_investors.'.$column, (float) $amount);
                }
            }
        });
    }
}

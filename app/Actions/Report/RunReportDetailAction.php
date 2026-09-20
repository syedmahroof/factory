<?php

namespace App\Actions\Report;

use App\Models\LiquidityLog;
use App\Models\MerchantInvestor;
use App\Models\MerchantPayment;
use App\Models\MerchantPaymentInvestor;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * The drill-downs behind the report rows.
 *
 * Five of the report tables expand a row into a child table — a merchant into its
 * payments, a payment into the investors it was split across, a batch into the
 * investors it moved liquidity for. In the legacy screens each was its own
 * DataTable endpoint reading `$request['merchant_id']` and friends; here they are
 * one endpoint keyed by report, so the parent key is validated and the child query
 * cannot be chosen freely.
 *
 * These are always scoped to a single parent, so they return every child row
 * rather than paging: the largest of them is one advance's investor list.
 */
class RunReportDetailAction
{
    /** report key => the parent id the drill-down needs. */
    public const PARENTS = [
        'payment' => 'merchant_id',
        'payment_details' => 'merchant_payment_id',
        'investment' => 'merchant_id',
        'merchant_default_rate' => 'merchant_id',
        'merchant_liquidity_log' => 'batch_no',
    ];

    /**
     * report key => the column on the parent row that holds that id.
     *
     * Not the same as PARENTS: the payment and investment reports are keyed on
     * `merchants.user_id`, which is the id their drill-down passes as `merchant_id`
     * — every merchant_id in this schema is a FK to users.id, not to merchants.id.
     */
    public const SOURCES = [
        'payment' => 'user_id',
        'payment_details' => 'id',
        'investment' => 'user_id',
        'merchant_default_rate' => 'merchant_id',
        'merchant_liquidity_log' => 'batch_no',
    ];

    public function handle(string $report, int|string $parent, array $filters): Collection
    {
        return match ($report) {
            'payment' => $this->paymentHistory((int) $parent, $filters),
            'payment_details' => $this->paymentInvestors((int) $parent, $filters),
            'investment' => $this->investmentInvestors((int) $parent, $filters),
            'merchant_default_rate' => $this->defaultRateInvestors((int) $parent, $filters),
            'merchant_liquidity_log' => $this->batchInvestors((string) $parent, $filters),
        };
    }

    /**
     * Every payment taken on one advance, with the split collapsed to totals.
     */
    private function paymentHistory(int $merchantId, array $filters): Collection
    {
        $basedOn = ($filters['based_on'] ?? 'date') === 'created_at' ? 'created_at' : 'date';

        return MerchantPayment::query()
            ->join('merchant_payment_investors', 'merchant_payment_investors.merchant_payment_id', 'merchant_payments.id')
            ->join('users as investors', 'merchant_payment_investors.investor_id', 'investors.id')
            ->where('merchant_payments.merchant_id', $merchantId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.investor_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $q->whereDate("merchant_payments.$basedOn", '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $q->whereDate("merchant_payments.$basedOn", '<=', $v))
            ->groupBy('merchant_payments.id')
            ->orderByDesc('merchant_payments.date')
            ->select(
                'merchant_payments.id',
                'merchant_payments.date',
                'merchant_payment_investors.merchant_id',
                'merchant_payments.amount as debited',
                DB::raw('sum(merchant_payment_investors.amount) as participant_share'),
                DB::raw('sum(merchant_payment_investors.management_fee) as management_fee'),
                DB::raw('sum(merchant_payment_investors.net_amount) as net_amount'),
                DB::raw('GROUP_CONCAT(investors.name) as investors'),
            )
            ->get()
            // The legacy screen rendered the concatenation as a grid of names.
            ->map(fn ($row) => tap($row, fn ($r) => $r->investors = array_filter(explode(',', (string) $r->investors))));
    }

    /**
     * How one payment was split: the fee cascade, per investor.
     */
    private function paymentInvestors(int $paymentId, array $filters): Collection
    {
        return MerchantPaymentInvestor::query()
            ->join('users as investors', 'investors.id', 'merchant_payment_investors.investor_id')
            ->leftJoin('users as companies', 'companies.id', 'merchant_payment_investors.company_id')
            ->leftJoin('rcodes', 'rcodes.id', 'merchant_payment_investors.rcode_id')
            ->where('merchant_payment_investors.merchant_payment_id', $paymentId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_payment_investors.investor_id', $v))
            ->orderBy('investors.name')
            ->select(
                'merchant_payment_investors.id',
                'merchant_payment_investors.investor_id',
                'merchant_payment_investors.date',
                'merchant_payment_investors.amount',
                'merchant_payment_investors.management_fee',
                'merchant_payment_investors.agent_fee',
                'merchant_payment_investors.net_amount',
                'merchant_payment_investors.principal',
                'merchant_payment_investors.profit',
                'investors.name as investor_name',
                'companies.name as company_name',
                'rcodes.code as rcode',
            )
            ->get();
    }

    /**
     * Who syndicated one advance, and on what terms.
     */
    private function investmentInvestors(int $merchantId, array $filters): Collection
    {
        $basedOn = ($filters['based_on'] ?? 'funded_date') === 'created_at' ? 'created_at' : 'funded_date';

        return MerchantInvestor::query()
            ->join('merchants', 'merchant_investors.merchant_id', 'merchants.user_id')
            ->join('users as investors', 'investors.id', 'merchant_investors.investor_id')
            ->where('merchant_investors.merchant_id', $merchantId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.investor_id', $v))
            ->when($filters['from_date'] ?? '', fn ($q, $v) => $basedOn === 'funded_date'
                ? $q->whereDate('merchants.funded_date', '>=', $v)
                : $q->whereDate('merchant_investors.created_at', '>=', $v))
            ->when($filters['to_date'] ?? '', fn ($q, $v) => $basedOn === 'funded_date'
                ? $q->whereDate('merchants.funded_date', '<=', $v)
                : $q->whereDate('merchant_investors.created_at', '<=', $v))
            ->orderBy('investors.name')
            ->select(
                'merchant_investors.id',
                'merchant_investors.investor_id',
                'investors.name as investor_name',
                'merchant_investors.funded',
                'merchant_investors.rtr',
                'merchant_investors.invested',
                'merchant_investors.investment_fee_amount',
                'merchant_investors.share',
                'merchant_investors.paid_management_fee',
                'merchants.funded_date',
            )
            ->get();
    }

    /**
     * What each investor stands to lose on a defaulted advance.
     */
    private function defaultRateInvestors(int $merchantId, array $filters): Collection
    {
        return MerchantInvestor::query()
            ->join('users as investors', 'investors.id', 'merchant_investors.investor_id')
            ->where('merchant_investors.merchant_id', $merchantId)
            ->when($filters['company_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.company_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_investors.investor_id', $v))
            // A zero-funded row is a placeholder, not a participation.
            ->where('merchant_investors.funded', '!=', 0)
            ->orderBy('investors.name')
            ->select(
                'merchant_investors.id',
                'merchant_investors.investor_id',
                'investors.name as investor_name',
                'merchant_investors.rtr',
                'merchant_investors.invested',
                'merchant_investors.paid_amount',
                'merchant_investors.management_fee_percentage',
                'merchant_investors.paid_net_amount',
            )
            ->get()
            ->map(function ($row) {
                // Principal still outstanding: what was put in, less what came back.
                $row->default_invested_amount = max(0, $row->invested - $row->paid_net_amount);
                // And the same against RTR, net of the management fee never charged.
                $row->default_rtr_amount = max(
                    0,
                    ($row->rtr - ($row->rtr * $row->management_fee_percentage / 100)) - $row->paid_net_amount,
                );

                return $row;
            });
    }

    /**
     * The investors one liquidity batch moved money for.
     */
    private function batchInvestors(string $batchNo, array $filters): Collection
    {
        return LiquidityLog::query()
            ->join('users as investors', 'investors.id', 'liquidity_logs.investor_id')
            ->where('batch_no', $batchNo)
            // A batch number is shared by the Investment and the Payment posted in
            // it, so the row's own description and creator narrow it to one posting.
            ->when($filters['descriptions'] ?? [], fn ($q, $v) => $q->whereIn('liquidity_logs.description', $v))
            ->when($filters['creator_id'] ?? null, fn ($q, $v) => $q->where('liquidity_logs.creator_id', $v))
            ->when($filters['investor_ids'] ?? [], fn ($q, $v) => $q->whereIn('investor_id', $v))
            ->when($filters['merchant_ids'] ?? [], fn ($q, $v) => $q->whereIn('merchant_id', $v))
            ->orderBy('investors.name')
            ->select(
                'liquidity_logs.id',
                'liquidity_logs.investor_id',
                'investors.name as investor_name',
                'liquidity_logs.amount',
                'liquidity_logs.net_liquidity',
            )
            ->get();
    }
}

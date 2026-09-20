<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Actions\Merchant\ListMerchantsAction;
use App\Http\Resources\Lender\LenderProfileResource;
use App\Models\Merchant;
use App\Models\User;
use Throwable;

/**
 * Everything the lender's own screen opens with: the fee-terms record, and what
 * the book it has funded currently looks like.
 */
class ShowLenderAction
{
    public function __construct(private ListMerchantsAction $merchants) {}

    public function handle(User $lender): ActionResult
    {
        try {
            $lender->loadMissing(['Company', 'Lender']);

            $book = $this->merchants->stats(['lender_id' => $lender->id]);
            $rtr = (float) $book['rtr'];
            $balance = (float) $book['balance'];

            $dates = Merchant::query()
                ->where('lender_id', $lender->id)
                ->selectRaw('MIN(funded_date) as first_funded_date')
                ->selectRaw('MAX(funded_date) as last_funded_date')
                ->first();

            $data = [
                'lender' => new LenderProfileResource($lender),
                'book' => [
                    'merchants' => (int) $book['total'],
                    'active' => (int) $book['active'],
                    'at_risk' => (int) $book['at_risk'],
                    'funded' => (float) $book['funded'],
                    'rtr' => $rtr,
                    'balance' => $balance,
                    'collected' => max($rtr - $balance, 0),
                    'collected_percentage' => $rtr > 0 ? min(max(($rtr - $balance) / $rtr * 100, 0), 100) : 0.0,
                    'first_funded_date' => $dates->first_funded_date ?? null,
                    'last_funded_date' => $dates->last_funded_date ?? null,
                ],
            ];

            return ActionResult::success($data);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load the lender.', 500);
        }
    }
}

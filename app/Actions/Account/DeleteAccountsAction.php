<?php

namespace App\Actions\Account;

use App\Actions\ActionResult;
use App\Exceptions\ActionException;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteAccountsAction
{
    public function handle(string $kind, array $ids): ActionResult
    {
        try {
            DB::transaction(function () use ($kind, $ids) {
                foreach ($ids as $id) {
                    $user = User::find($id);

                    if (! $user) {
                        throw new ActionException('Invalid id');
                    }

                    switch ($kind) {
                        case 'User':
                        case 'Investor':
                            $user->delete();
                            break;
                        case 'Merchant':
                            $this->deleteMerchant($user);
                            break;
                    }
                }
            });

            return ActionResult::success();
        } catch (ActionException $e) {
            return ActionResult::failure($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to delete account(s).', 500);
        }
    }

    private function deleteMerchant(User $user): void
    {
        $user->Merchant()->delete();

        foreach ($user->MerchantInvestors as $merchantInvestor) {
            $merchantInvestor->MerchantInvestorFees()->delete();
            if (! $merchantInvestor->delete()) {
                throw new ActionException("Cant Delete This MerchantInvestor{$merchantInvestor->id}");
            }
        }
    }
}

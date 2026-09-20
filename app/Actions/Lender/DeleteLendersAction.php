<?php

namespace App\Actions\Lender;

use App\Actions\ActionResult;
use App\Exceptions\ActionException;
use App\Models\Lender;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeleteLendersAction
{
    /**
     * Delete lenders.
     *
     * @param  array  $ids  the user IDs of the lenders to delete
     */
    public function handle(array $ids): ActionResult
    {
        try {
            DB::transaction(function () use ($ids) {
                $lenders = User::whereIn('id', $ids)
                    ->where('user_type_id', UserType::Lender)
                    ->get();

                if ($lenders->count() !== count(array_unique($ids))) {
                    throw new ActionException('One of the selected rows is not a lender.');
                }

                Lender::whereIn('user_id', $lenders->pluck('id'))->delete();

                foreach ($lenders as $lender) {
                    $lender->delete();
                }
            });

            return ActionResult::success(null, 'Lender(s) deleted successfully.');
        } catch (ActionException $e) {
            return ActionResult::failure($e->getMessage(), 422);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to delete lender(s).', 500);
        }
    }
}

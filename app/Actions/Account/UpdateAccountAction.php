<?php

namespace App\Actions\Account;

use App\Actions\ActionResult;
use App\Http\Resources\Account\AccountResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Throwable;

class UpdateAccountAction
{
    /**
     * Update a user account.
     */
    public function handle(User $user, array $attributes): ActionResult
    {
        try {
            $data = [
                'user_type_id' => $attributes['user_type_id'] ?? $user->user_type_id,
                'company_id' => $attributes['company_id'] ?? $user->company_id,
                'status_id' => ($attributes['status_id'] ?? null) ?: $user->status_id,
                'name' => $attributes['name'] ?? $user->name,
                'email' => $attributes['email'] ?? $user->email,
                'cell_phone' => $attributes['cell_phone'] ?? $user->cell_phone,
                'liquidity' => $attributes['liquidity'] ?? $user->liquidity,
            ];

            if (filled($attributes['password'] ?? null)) {
                $data['password'] = Hash::make($attributes['password']);
            }

            DB::transaction(fn () => $user->update($data));

            return ActionResult::success(new AccountResource($user), 'Account updated successfully.');
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to update account.', 500);
        }
    }
}

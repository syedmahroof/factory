<?php

namespace App\Actions\Account;

use App\Actions\ActionResult;
use App\Http\Resources\Account\AccountResource;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Throwable;

class CreateAccountAction
{
    /**
     * Create a user account.
     */
    public function handle(array $attributes): ActionResult
    {
        try {
            $data = [
                'user_type_id' => $attributes['user_type_id'],
                'company_id' => $attributes['company_id'] ?? null,
                'status_id' => ($attributes['status_id'] ?? null) ?: User::Active,
                'name' => $attributes['name'],
                'email' => $attributes['email'],
                'cell_phone' => $attributes['cell_phone'] ?? null,
                'liquidity' => ($attributes['liquidity'] ?? null) ?: 0,
                'mysql_2_merchant_id' => $attributes['mysql_2_merchant_id'] ?? null,
                'password' => Hash::make(
                    filled($attributes['password'] ?? null) ? $attributes['password'] : Str::password(32)
                ),
            ];

            $user = DB::transaction(fn () => User::create($data));

            return ActionResult::success(new AccountResource($user), 'Account created successfully.');
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to create account.', 500);
        }
    }
}

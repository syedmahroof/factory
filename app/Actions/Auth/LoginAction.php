<?php

namespace App\Actions\Auth;

use App\Actions\ActionResult;
use App\Enums\UserStatus;
use App\Enums\UserType;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class LoginAction
{
    /**
     * Exchange credentials for a Sanctum personal access token.
     *
     * Deliberately never reveals which half of the pair was wrong, and runs the
     * hash check even when no user matched so a missing account and a bad password
     * take the same time to fail.
     */
    public function handle(string $email, string $password, bool $remember = false): ActionResult
    {
        $user = User::where('email', $email)->first();

        if (! $user || ! Hash::check($password, $user->password)) {
            // Burn a hash cycle on the miss path to keep the timing flat.
            if (! $user) {
                Hash::make($password);
            }

            return ActionResult::failure('These credentials do not match our records.', 401);
        }

        if ((int) $user->status_id === UserStatus::Deactive->value) {
            return ActionResult::failure('This account has been deactivated.', 403);
        }

        $type = UserType::tryFrom((int) $user->user_type_id);

        if (! $type || $type->isPseudoInvestor() || ! $type->portal()) {
            // OverPayment / AgentFee / MerchantFees are ledger placeholders used by
            // the payment split, not people. They must never hold a token.
            return ActionResult::failure('This account cannot sign in.', 403);
        }

        $abilities = $type->abilities();

        // A "remember me" token simply outlives the default TTL; Sanctum has no
        // separate remember concept, so expiry is the only thing that changes.
        $expiresAt = $remember
            ? now()->addDays(30)
            : (config('sanctum.expiration') ? now()->addMinutes(config('sanctum.expiration')) : null);

        $token = $user->createToken("portal:{$type->portal()->value}", $abilities, $expiresAt);

        return ActionResult::success([
            'token' => $token->plainTextToken,
            'user' => $user,
            'abilities' => $abilities,
        ]);
    }
}

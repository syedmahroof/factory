<?php

namespace App\Actions\Auth;

use App\Actions\ActionResult;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class ResetPasswordAction
{
    public function handle(array $credentials): ActionResult
    {
        $status = Password::reset($credentials, function (User $user, string $password) {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            // Every existing token is revoked: a password reset is the one moment
            // where signing every other device out is the point.
            $user->tokens()->delete();

            event(new PasswordReset($user));
        });

        if ($status !== Password::PASSWORD_RESET) {
            return ActionResult::failure(__($status), 422);
        }

        return ActionResult::success(message: 'Your password has been reset. Sign in with it now.');
    }
}

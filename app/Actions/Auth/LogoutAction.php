<?php

namespace App\Actions\Auth;

use App\Actions\ActionResult;
use App\Models\User;

class LogoutAction
{
    /**
     * Revoke only the token that made this request, so signing out on one device
     * leaves the others alone.
     */
    public function handle(User $user): ActionResult
    {
        $user->currentAccessToken()?->delete();

        return ActionResult::success(message: 'Signed out.');
    }
}

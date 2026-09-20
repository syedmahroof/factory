<?php

namespace App\Actions\Auth;

use App\Actions\ActionResult;
use Illuminate\Support\Facades\Password;

class SendPasswordResetLinkAction
{
    /**
     * Always reports success, whatever the broker says.
     *
     * A distinct "we have no such user" reply turns this endpoint into a free
     * account-enumeration oracle. Real failures are still logged server-side.
     */
    public function handle(string $email): ActionResult
    {
        $status = Password::sendResetLink(['email' => $email]);

        if ($status !== Password::RESET_LINK_SENT) {
            logger()->info('Password reset link not sent.', ['email' => $email, 'status' => $status]);
        }

        return ActionResult::success(message: 'If that email is registered, a reset link is on its way.');
    }
}

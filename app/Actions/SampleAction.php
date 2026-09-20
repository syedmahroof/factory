<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Template for Action classes in this project.
 *
 * Rules:
 *  - One action = one job, one public handle() method.
 *  - Typed input (models, scalars, DTOs) — never an untyped array bag.
 *  - Always returns ActionResult, so every key exists on every path.
 *  - Multi-write work runs inside DB::transaction() so a failure rolls
 *    everything back before the catch below converts it to a result.
 *  - Expected failures are guard clauses returning ActionResult::failure().
 *  - Unexpected exceptions are report()-ed (logged with full stack trace)
 *    and converted to a GENERIC failure message — never return
 *    $e->getMessage() to the caller, it can leak SQL and internals.
 *  - Dependencies are constructor-injected, not new-ed inside handle().
 *
 * This sample deactivates a user and revokes their Sanctum tokens.
 */
class SampleAction
{
    public function handle(User $user): ActionResult
    {
        // Expected failures: check up front, fail with a clear message.
        if ((int) $user->status_id === 0) {
            return ActionResult::failure('Account is already deactivated.', 422);
        }

        try {
            DB::transaction(function () use ($user) {
                $user->forceFill(['status_id' => 0])->save();

                $user->tokens()->delete();
            });

            return ActionResult::success($user->fresh(), 'Account deactivated.');
        } catch (\Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to deactivate account.', 500);
        }
    }
}

/*
|--------------------------------------------------------------------------
| Usage
|--------------------------------------------------------------------------
|
| Web controller (caller owns the user-facing wording):
|
|     public function deactivate(User $user, SampleAction $action)
|     {
|         $result = $action->handle($user);
|
|         return $result->success
|             ? back()->with('success', $result->message)
|             : back()->with('error', $result->message);
|     }
|
| API controller:
|
|     public function deactivate(User $user, SampleAction $action)
|     {
|         return $action->handle($user)->toResponse();
|     }
*/

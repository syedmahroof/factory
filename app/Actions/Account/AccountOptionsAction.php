<?php

namespace App\Actions\Account;

use App\Actions\ActionResult;
use App\Models\User;
use App\Models\UserType;
use Throwable;

class AccountOptionsAction
{
    /**
     * Dropdown data for the account filters and the create/edit form.
     */
    public function handle(): ActionResult
    {
        try {
            $options = [
                'user_types' => UserType::whereIn('id', UserType::accountTypeIds())
                    ->orderBy('name')
                    ->get(['id', 'name']),
                'statuses' => collect(User::statusOptions())
                    ->map(fn ($name, $id) => ['id' => $id, 'name' => $name])
                    ->values(),
                'companies' => User::where('user_type_id', UserType::Company)
                    ->orderBy('name')
                    ->get(['id', 'name']),
            ];

            return ActionResult::success($options);
        } catch (Throwable $e) {
            report($e);

            return ActionResult::failure('Unable to load form options.', 500);
        }
    }
}

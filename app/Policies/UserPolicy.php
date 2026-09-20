<?php

namespace App\Policies;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;

/**
 * First policy on the platform.
 *
 * Until now every admin route sat behind a bare middleware('auth'), so any
 * authenticated actor — an investor included — could reach admin URLs. These
 * checks are therefore NEW behaviour, not a port. Anything that used to work by
 * accident and stops working here is the bug being fixed.
 */
class UserPolicy
{
    use HandlesAuthorization;

    /**
     * Admins bypass the rest. Kept deliberately narrow: only the Admin type,
     * not every back-office type.
     */
    public function before(User $actor): ?bool
    {
        return (int) $actor->user_type_id === UserType::Admin->value ? true : null;
    }

    public function viewAny(User $actor): bool
    {
        return $this->isBackOffice($actor);
    }

    public function view(User $actor, User $account): bool
    {
        return $this->isBackOffice($actor) || $actor->id === $account->id;
    }

    public function create(User $actor): bool
    {
        return $this->isBackOffice($actor);
    }

    public function update(User $actor, User $account): bool
    {
        return $this->isBackOffice($actor) || $actor->id === $account->id;
    }

    /**
     * Deleting accounts stays with Admin only — `before()` already granted it,
     * so reaching this line means the actor is not an admin.
     */
    public function delete(User $actor, User $account): bool
    {
        return false;
    }

    public function export(User $actor): bool
    {
        return $this->isBackOffice($actor);
    }

    /**
     * Back-office types that manage accounts but are not full admins.
     */
    private function isBackOffice(User $actor): bool
    {
        return in_array((int) $actor->user_type_id, [
            UserType::BranchManager->value,
            UserType::Editor->value,
            UserType::EditorWithCreditCardAccess->value,
            UserType::Accounts->value,
        ], true);
    }
}

<?php

namespace App\Http\Requests\Lender;

use App\Models\User;
use App\Models\UserType;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * `{lender}` binds to a User, and a User is not necessarily a lender — without
 * this, any users row could be read or written through the lender endpoints
 * just by putting its id in the URL.
 */
trait ResolvesLender
{
    protected function lender(): User
    {
        $lender = $this->route('lender');

        if (! $lender instanceof User || (int) $lender->user_type_id !== UserType::Lender) {
            throw new NotFoundHttpException('No lender found.');
        }

        return $lender;
    }
}

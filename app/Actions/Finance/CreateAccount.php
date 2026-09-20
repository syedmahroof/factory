<?php

namespace App\Actions\Finance;

use App\Models\Account;
use App\Services\AuditService;

class CreateAccount
{
    public function execute(array $data): Account
    {
        $account = Account::create($data);
        AuditService::log('created', 'finance', $account, null, $data);

        return $account;
    }
}

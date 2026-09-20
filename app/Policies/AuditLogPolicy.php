<?php
namespace App\Policies;

use App\Models\User;

class AuditLogPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

<?php
namespace App\Policies;

use App\Models\User;

class PermitToWorkPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

<?php
namespace App\Policies;

use App\Models\User;

class MaintenanceOrderPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

<?php
namespace App\Policies;

use App\Models\User;

class PlannedOrderPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

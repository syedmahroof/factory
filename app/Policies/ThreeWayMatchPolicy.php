<?php
namespace App\Policies;

use App\Models\User;

class ThreeWayMatchPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

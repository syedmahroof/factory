<?php
namespace App\Policies;

use App\Models\User;

class SafetyIncidentPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

<?php
namespace App\Policies;

use App\Models\User;

class PreventiveMaintenanceSchedulePolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

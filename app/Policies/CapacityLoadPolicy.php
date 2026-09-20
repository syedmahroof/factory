<?php
namespace App\Policies;

use App\Models\User;

class CapacityLoadPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

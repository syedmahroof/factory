<?php
namespace App\Policies;

use App\Models\User;

class CustomerPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

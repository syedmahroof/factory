<?php
namespace App\Policies;

use App\Models\User;

class CustomerCreditExposurePolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

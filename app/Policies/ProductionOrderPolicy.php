<?php
namespace App\Policies;

use App\Models\User;

class ProductionOrderPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

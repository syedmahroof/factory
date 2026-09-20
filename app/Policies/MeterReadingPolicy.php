<?php
namespace App\Policies;

use App\Models\User;

class MeterReadingPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

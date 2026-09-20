<?php
namespace App\Policies;

use App\Models\User;

class FixedAssetPolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

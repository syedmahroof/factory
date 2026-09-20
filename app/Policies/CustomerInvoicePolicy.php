<?php
namespace App\Policies;

use App\Models\User;

class CustomerInvoicePolicy extends BasePolicy
{
    public function __construct()
    {
        // Uses BasePolicy defaults
    }
}

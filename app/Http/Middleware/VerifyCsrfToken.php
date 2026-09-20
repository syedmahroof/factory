<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        // Authenticated by a Sanctum bearer token, not by the session it is
        // about to create — there is no session token to verify yet.
        'app/session',
        'app/session/destroy',
    ];
}

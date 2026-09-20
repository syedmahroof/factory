<?php

namespace Integrations\Crm\Http\Middleware;

use App\Models\ApiClient;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateCrm
{
    public function handle(Request $request, Closure $next): Response
    {
        $username = $request->input('PHP_AUTH_USER');
        $password = $request->input('PHP_AUTH_PW');

        $client = ApiClient::where('username', $username)->first();

        if (! $client || ! Hash::check((string) $password, $client->password)) {
            return response()->json([
                'message' => 'Invalid API credentials.',
                'status' => 0,
            ], 401);
        }

        $request->attributes->set('api_client', $client);

        return $next($request);
    }
}

<?php

namespace App\Http\Controllers\Api\V1\Factory;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AuthController extends BaseController
{
    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required']);
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return $this->error('Invalid credentials', 401);
        }
        $token = $user->createToken('factory-erp')->plainTextToken;

        return $this->success(['user' => $user->load('roles'), 'token' => $token], 'Logged in');
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return $this->success(null, 'Logged out');
    }

    public function me(Request $request)
    {
        // `company`, not `companies`: a user belongs to one. Eager-loading a
        // relationship the model does not declare threw on every call, which
        // the SPA read as a dead session and logged itself out on refresh.
        return $this->success($request->user()->load('roles', 'company', 'plant', 'department'));
    }
}

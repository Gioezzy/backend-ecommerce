<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'email_verified_at' => now(),
        ]);

        $token = auth('api')->login($user);

        return $this->respondWithToken($token);
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (! $token = auth('api')->attempt($credentials)) {
            return response()->json([
                'action' => 'login',
                'status' => 'error',
                'message' => 'Invalid login credentials',
            ], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout(Request $request)
    {
        auth('api')->logout();

        return response()->json([
            'action' => 'logout',
            'status' => 'success',
            'message' => 'Logged out successfully',
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
             'action' => 'get_user',
             'status' => 'success',
             'data' => auth('api')->user(),
        ]);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'status' => 'success',
            'data' => [
                'user' => auth('api')->user(),
                'token' => $token,
                'token_type' => 'Bearer',
                'expires_in' => auth('api')->factory()->getTTL() * 60
            ]
        ]);
    }
}

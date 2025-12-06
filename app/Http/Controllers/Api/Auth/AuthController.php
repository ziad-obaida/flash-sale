<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Api\BaseController;
use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class AuthController extends BaseController
{
    /**
     * Register a new user and return API token
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed|min:6'
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password)
        ]);

        $token = $user->createToken('api_token')->plainTextToken;

        Log::channel('auth')->info('User created', ['user_id' => $user->id]);

        return $this->sendResponse(true, 'User registered successfully', [
            'token' => $token,
            'user'  => $user
        ], 201);
    }

    /**
     * Login user and return API token
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string'
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::channel('auth')->warning('Failed login attempt', ['email' => $request->email]);
            return $this->sendError('Invalid credentials', 401);
        }

        $token = $user->createToken('api_token')->plainTextToken;
        Log::channel('auth')->info('User logged in', ['user_id' => $user->id]);

        return $this->sendResponse(true, 'Login successful', [
            'token' => $token,
            'user'  => $user
        ]);
    }

    /**
     * Logout user by deleting current token
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();
        Log::channel('auth')->info('User logged out', ['user_id' => $request->user()->id]);
        return $this->sendResponse(true, 'Logged out successfully');
    }
}

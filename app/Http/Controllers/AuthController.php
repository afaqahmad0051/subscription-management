<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\LoginRequest;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\RegisterUserRequest;

class AuthController extends Controller
{
    public function register(RegisterUserRequest $request): JsonResponse
    {
        $validated = $request->validated();
        if (User::where('email', $validated['email'])->exists()) {
            return response()->json([
                'error' => true,
                'message' => 'Email already exists.',
            ], 409);
        }

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make('password'),
            'role' => UserRole::User->value,
        ]);

        return response()->json([
            'error' => false,
            'message' => 'User registered successfully.',
            'data' => $user,
        ], 200);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->validated();
        $user = User::where('email', $credentials['email'])->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            return response()->json([
                'error' => true,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        $user->tokens()->delete();
        $token = $user->createToken($user->name)->plainTextToken;

        $response = [
            'token' => $token,
            'name' => $user->name,
        ];

        return response()->json([
            'error' => false,
            'message' => 'User logged in successfully.',
            'data' => $response,
        ], 200);
    }
}

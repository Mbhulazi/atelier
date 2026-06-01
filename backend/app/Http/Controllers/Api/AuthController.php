<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function register(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Register endpoint']);
    }

    public function login(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Login endpoint']);
    }

    public function logout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Logged out']);
    }

    public function user(Request $request): JsonResponse
    {
        return response()->json(['user' => $request->user()]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Profile updated']);
    }

    public function changePassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Password changed']);
    }

    public function forgotPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Password reset link sent']);
    }

    public function resetPassword(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Password reset successfully']);
    }
}

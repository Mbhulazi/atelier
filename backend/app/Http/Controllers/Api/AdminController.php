<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function users(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function updateUserRole(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'User role updated']);
    }

    public function deleteUser(string $id): JsonResponse
    {
        return response()->json(['message' => 'User deleted']);
    }

    public function stats(Request $request): JsonResponse
    {
        return response()->json([
            'users' => 0,
            'painters' => 0,
            'paintings' => 0,
            'videos' => 0,
            'orders' => 0,
            'revenue' => 0,
        ]);
    }
}

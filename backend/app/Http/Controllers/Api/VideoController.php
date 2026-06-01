<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function show(string $id): JsonResponse
    {
        return response()->json(['data' => null]);
    }

    public function dashboardIndex(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function store(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Video created'], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        return response()->json(['message' => 'Video updated']);
    }

    public function destroy(string $id): JsonResponse
    {
        return response()->json(['message' => 'Video deleted']);
    }
}

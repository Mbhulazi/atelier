<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function checkout(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Checkout created'], 201);
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        return response()->json(['data' => null]);
    }

    public function painterOrders(Request $request): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function earnings(Request $request): JsonResponse
    {
        return response()->json(['earnings' => 0]);
    }

    public function webhook(Request $request): JsonResponse
    {
        return response()->json(['received' => true]);
    }
}

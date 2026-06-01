<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainterController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function show(string $slug): JsonResponse
    {
        return response()->json(['data' => null]);
    }

    public function paintings(string $slug): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function videos(string $slug): JsonResponse
    {
        return response()->json(['data' => []]);
    }

    public function stripeOnboard(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Stripe onboard']);
    }

    public function stripeStatus(Request $request): JsonResponse
    {
        return response()->json(['status' => null]);
    }

    public function stripeRefresh(Request $request): JsonResponse
    {
        return response()->json(['message' => 'Stripe link refreshed']);
    }
}

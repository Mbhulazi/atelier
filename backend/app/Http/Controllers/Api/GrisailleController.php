<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GrisailleAnalysis;
use App\Jobs\GenerateGrisaillePdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class GrisailleController extends Controller
{
    public function analyze(Request $request): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|max:20480',
            'value_map' => 'required|json',
            'value_percentages' => 'required|json',
            'palette' => 'required|json',
            'glazings' => 'required|json',
        ]);

        $imagePath = $request->file('image')->store('grisaille', 'public');

        $analysis = GrisailleAnalysis::create([
            'user_id' => $request->user()->id,
            'original_image_url' => $imagePath,
            'value_map' => json_decode($request->value_map, true),
            'value_percentages' => json_decode($request->value_percentages, true),
            'palette_recommendation' => json_decode($request->palette, true),
            'glazing_suggestions' => json_decode($request->glazings, true),
        ]);

        GenerateGrisaillePdf::dispatch($analysis);

        return response()->json([
            'id' => $analysis->id,
            'message' => 'Analysis saved. PDF will be available shortly.',
        ], 201);
    }

    public function history(Request $request): JsonResponse
    {
        $analyses = GrisailleAnalysis::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(12);

        return response()->json($analyses);
    }

    public function downloadPdf(Request $request, string $id): JsonResponse
    {
        $analysis = GrisailleAnalysis::where('user_id', $request->user()->id)
            ->findOrFail($id);

        if (!$analysis->pdf_url) {
            return response()->json(['message' => 'PDF not ready yet'], 404);
        }

        $url = Storage::disk('public')->url($analysis->pdf_url);

        return response()->json(['url' => $url]);
    }
}

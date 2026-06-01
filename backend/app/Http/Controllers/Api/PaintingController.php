<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Painting;
use App\Models\PaintingImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManagerStatic as Image;

class PaintingController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $paintings = Painting::with(['images', 'user'])
            ->where('is_available', true)
            ->when($request->style, fn($q) => $q->where('style', $request->style))
            ->when($request->medium, fn($q) => $q->where('medium', $request->medium))
            ->when($request->min_price, fn($q) => $q->where('price', '>=', $request->min_price))
            ->when($request->max_price, fn($q) => $q->where('price', '<=', $request->max_price))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($paintings);
    }

    public function show(string $id): JsonResponse
    {
        $painting = Painting::with(['images', 'user.painterProfile'])
            ->findOrFail($id);

        return response()->json($painting);
    }

    public function dashboardIndex(Request $request): JsonResponse
    {
        $paintings = Painting::where('user_id', $request->user()->id)
            ->with('images')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($paintings);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'medium' => 'nullable|string',
            'style' => 'nullable|string',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'year_created' => 'nullable|integer|min:1900|max:' . date('Y'),
            'tags' => 'nullable|array',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|max:10240',
        ]);

        $painting = Painting::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'medium' => $validated['medium'] ?? null,
            'style' => $validated['style'] ?? null,
            'width' => $validated['width'] ?? null,
            'height' => $validated['height'] ?? null,
            'depth' => $validated['depth'] ?? null,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'year_created' => $validated['year_created'] ?? null,
            'tags' => $validated['tags'] ?? null,
        ]);

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('paintings', 'public');
            PaintingImage::create([
                'painting_id' => $painting->id,
                'image_url' => $path,
                'sort_order' => $index,
                'is_primary' => $index === 0,
            ]);
        }

        return response()->json([
            'painting' => $painting->load('images'),
            'message' => 'Painting created successfully',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $painting = Painting::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:2000',
            'medium' => 'nullable|string',
            'style' => 'nullable|string',
            'width' => 'nullable|numeric|min:0',
            'height' => 'nullable|numeric|min:0',
            'depth' => 'nullable|numeric|min:0',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'year_created' => 'nullable|integer',
            'tags' => 'nullable|array',
            'is_available' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
        ]);

        $painting->update($validated);

        if ($request->has('removed_images')) {
            foreach ($request->removed_images as $imageId) {
                $image = PaintingImage::where('painting_id', $painting->id)->where('id', $imageId)->first();
                if ($image) {
                    Storage::disk('public')->delete($image->image_url);
                    $image->delete();
                }
            }
        }

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $index => $image) {
                $path = $image->store('paintings', 'public');
                PaintingImage::create([
                    'painting_id' => $painting->id,
                    'image_url' => $path,
                    'sort_order' => $painting->images()->count() + $index,
                    'is_primary' => false,
                ]);
            }
        }

        return response()->json([
            'painting' => $painting->load('images'),
            'message' => 'Painting updated successfully',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $painting = Painting::where('user_id', $request->user()->id)
            ->findOrFail($id);

        foreach ($painting->images as $image) {
            Storage::disk('public')->delete($image->image_url);
        }

        $painting->delete();

        return response()->json(['message' => 'Painting deleted successfully']);
    }
}

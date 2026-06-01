<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Video;
use App\Models\VideoPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class VideoController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $videos = Video::with('user')
            ->where('is_published', true)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->when($request->difficulty, fn($q) => $q->where('difficulty', $request->difficulty))
            ->when($request->free, fn($q) => $q->where('is_free', true))
            ->when($request->search, fn($q) => $q->where('title', 'like', "%{$request->search}%"))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($videos);
    }

    public function show(string $id): JsonResponse
    {
        $video = Video::with('user')->findOrFail($id);

        if (!$video->is_published && $video->user_id !== request()->user()?->id) {
            abort(404);
        }

        $video->incrementViews();

        return response()->json($video);
    }

    public function dashboardIndex(Request $request): JsonResponse
    {
        $videos = Video::where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($videos);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'video' => 'required|file|mimes:mp4,avi,mov,wmv|max:512000',
            'thumbnail' => 'nullable|image|max:5120',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'category' => 'nullable|string',
            'difficulty' => 'nullable|in:beginner,intermediate,advanced',
            'tags' => 'nullable|array',
            'is_free' => 'boolean',
        ]);

        $videoPath = $request->file('video')->store('videos', 'public');

        $thumbnailPath = null;
        if ($request->hasFile('thumbnail')) {
            $thumbnailPath = $request->file('thumbnail')->store('video-thumbnails', 'public');
        }

        $video = Video::create([
            'user_id' => $request->user()->id,
            'title' => $validated['title'],
            'description' => $validated['description'] ?? null,
            'video_url' => $videoPath,
            'thumbnail_url' => $thumbnailPath,
            'price' => $validated['price'] ?? null,
            'currency' => $validated['currency'] ?? 'USD',
            'category' => $validated['category'] ?? null,
            'difficulty' => $validated['difficulty'] ?? null,
            'tags' => $validated['tags'] ?? null,
            'is_free' => $validated['is_free'] ?? false,
        ]);

        return response()->json([
            'video' => $video,
            'message' => 'Video uploaded successfully',
        ], 201);
    }

    public function update(Request $request, string $id): JsonResponse
    {
        $video = Video::where('user_id', $request->user()->id)
            ->findOrFail($id);

        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string|max:5000',
            'video' => 'nullable|file|mimes:mp4,avi,mov,wmv|max:512000',
            'thumbnail' => 'nullable|image|max:5120',
            'price' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:3',
            'category' => 'nullable|string',
            'difficulty' => 'nullable|in:beginner,intermediate,advanced',
            'tags' => 'nullable|array',
            'is_free' => 'nullable|boolean',
            'is_published' => 'nullable|boolean',
            'is_featured' => 'nullable|boolean',
        ]);

        if ($request->hasFile('video')) {
            Storage::disk('public')->delete($video->video_url);
            $validated['video_url'] = $request->file('video')->store('videos', 'public');
            unset($validated['video']);
        }

        if ($request->hasFile('thumbnail')) {
            if ($video->thumbnail_url) {
                Storage::disk('public')->delete($video->thumbnail_url);
            }
            $validated['thumbnail_url'] = $request->file('thumbnail')->store('video-thumbnails', 'public');
            unset($validated['thumbnail']);
        }

        $video->update($validated);

        return response()->json([
            'video' => $video,
            'message' => 'Video updated successfully',
        ]);
    }

    public function destroy(Request $request, string $id): JsonResponse
    {
        $video = Video::where('user_id', $request->user()->id)
            ->findOrFail($id);

        Storage::disk('public')->delete($video->video_url);
        if ($video->thumbnail_url) {
            Storage::disk('public')->delete($video->thumbnail_url);
        }

        $video->delete();

        return response()->json(['message' => 'Video deleted successfully']);
    }

    public function purchase(Request $request, string $id): JsonResponse
    {
        $video = Video::where('is_published', true)->findOrFail($id);

        if ($video->is_free) {
            return response()->json(['message' => 'This video is free'], 400);
        }

        $existingPurchase = VideoPurchase::where('user_id', $request->user()->id)
            ->where('video_id', $video->id)
            ->where('status', 'completed')
            ->first();

        if ($existingPurchase) {
            return response()->json(['message' => 'You already own this video'], 400);
        }

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));

        $session = $stripe->checkout->sessions->create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($video->currency),
                    'product_data' => [
                        'name' => $video->title,
                        'description' => $video->description ? Str::limit($video->description, 200) : null,
                    ],
                    'unit_amount' => (int) ($video->price * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => config('app.url') . "/video/{$video->id}?purchased=1",
            'cancel_url' => config('app.url') . "/video/{$video->id}",
            'metadata' => [
                'video_id' => $video->id,
                'user_id' => $request->user()->id,
            ],
        ]);

        VideoPurchase::create([
            'user_id' => $request->user()->id,
            'video_id' => $video->id,
            'amount' => $video->price,
            'currency' => $video->currency,
            'stripe_session_id' => $session->id,
            'status' => 'pending',
        ]);

        return response()->json(['url' => $session->url]);
    }

    public function checkAccess(Request $request, string $id): JsonResponse
    {
        $video = Video::findOrFail($id);

        if ($video->is_free) {
            return response()->json(['has_access' => true]);
        }

        $hasAccess = VideoPurchase::where('user_id', $request->user()->id)
            ->where('video_id', $video->id)
            ->where('status', 'completed')
            ->exists();

        return response()->json(['has_access' => $hasAccess]);
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PainterController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::where('role', 'painter')
            ->with('painterProfile')
            ->withCount('paintings')
            ->withCount('videos');

        if ($request->featured) {
            $query->whereHas('painterProfile', fn($q) => $q->where('is_featured', true));
        }

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('bio', 'like', "%{$request->search}%");
            });
        }

        if ($request->specialty) {
            $query->whereHas('painterProfile', function ($q) use ($request) {
                $q->whereJsonContains('specialties', $request->specialty);
            });
        }

        $painters = $query->orderBy('name')
            ->paginate($request->get('limit', 12));

        return response()->json($painters);
    }

    public function show(string $slug): JsonResponse
    {
        $painter = User::where('slug', $slug)
            ->where('role', 'painter')
            ->with('painterProfile')
            ->withCount('paintings')
            ->withCount('videos')
            ->firstOrFail();

        return response()->json(['painter' => $painter]);
    }

    public function paintings(Request $request, string $slug): JsonResponse
    {
        $painter = User::where('slug', $slug)->where('role', 'painter')->firstOrFail();

        $paintings = $painter->paintings()
            ->with('images')
            ->when($request->style, fn($q) => $q->where('style', $request->style))
            ->when($request->medium, fn($q) => $q->where('medium', $request->medium))
            ->when($request->available, fn($q) => $q->where('is_available', true))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($paintings);
    }

    public function videos(Request $request, string $slug): JsonResponse
    {
        $painter = User::where('slug', $slug)->where('role', 'painter')->firstOrFail();

        $videos = $painter->videos()
            ->where('is_published', true)
            ->when($request->category, fn($q) => $q->where('category', $request->category))
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($videos);
    }

    public function projects(Request $request, string $slug): JsonResponse
    {
        $painter = User::where('slug', $slug)->where('role', 'painter')->firstOrFail();

        $projects = $painter->projects()
            ->where('is_public', true)
            ->orderBy('sort_order')
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('limit', 12));

        return response()->json($projects);
    }

    public function stripeOnboard(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
            $account = $stripe->accounts->create([
                'type' => 'express',
                'email' => $user->email,
                'capabilities' => [
                    'card_payments' => ['requested' => true],
                    'transfers' => ['requested' => true],
                ],
            ]);
            $user->update(['stripe_account_id' => $account->id]);
        }

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $accountLink = $stripe->accountLinks->create([
            'account' => $user->stripe_account_id,
            'refresh_url' => route('stripe.refresh'),
            'return_url' => route('stripe.return'),
            'type' => 'account_onboarding',
        ]);

        return response()->json(['url' => $accountLink->url]);
    }

    public function stripeStatus(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->stripe_account_id) {
            return response()->json(['connected' => false]);
        }

        $stripe = new \Stripe\StripeClient(config('services.stripe.secret'));
        $account = $stripe->accounts->retrieve($user->stripe_account_id);

        return response()->json([
            'connected' => $account->charges_enabled && $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
        ]);
    }

    public function stripeRefresh(Request $request): JsonResponse
    {
        return $this->stripeOnboard($request);
    }
}

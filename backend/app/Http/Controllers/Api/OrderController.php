<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Painting;
use App\Models\Video;
use App\Models\VideoPurchase;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Stripe\StripeClient;

class OrderController extends Controller
{
    private const COMMISSION_RATE = 0.15; // 15%

    public function checkout(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type' => 'required|in:painting,video',
            'painting_id' => 'required_if:type,painting|nullable|exists:paintings,id',
            'video_id' => 'required_if:type,video|nullable|exists:videos,id',
            'shipping_address' => 'nullable|string|max:1000',
            'notes' => 'nullable|string|max:500',
        ]);

        if ($validated['type'] === 'painting') {
            $painting = Painting::with('user')->findOrFail($validated['painting_id']);

            if (!$painting->is_available) {
                return response()->json(['message' => 'This painting is no longer available'], 400);
            }

            if ($painting->user_id === $request->user()->id) {
                return response()->json(['message' => 'You cannot buy your own painting'], 400);
            }

            $subtotal = $painting->price;
            $painterId = $painting->user_id;
        } else {
            $video = Video::with('user')->findOrFail($validated['video_id']);

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

            $subtotal = $video->price;
            $painterId = $video->user_id;
        }

        $commissionAmount = round($subtotal * self::COMMISSION_RATE, 2);
        $payoutAmount = $subtotal - $commissionAmount;

        $order = Order::create([
            'buyer_id' => $request->user()->id,
            'painter_id' => $painterId,
            'painting_id' => $validated['painting_id'] ?? null,
            'video_id' => $validated['video_id'] ?? null,
            'type' => $validated['type'],
            'subtotal' => $subtotal,
            'commission_rate' => self::COMMISSION_RATE,
            'commission_amount' => $commissionAmount,
            'payout_amount' => $payoutAmount,
            'total' => $subtotal,
            'shipping_address' => $validated['shipping_address'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'status' => 'pending',
        ]);

        $stripe = new StripeClient(config('services.stripe.secret'));

        $lineItem = [
            'price_data' => [
                'currency' => strtolower($order->currency),
                'product_data' => [
                    'name' => $validated['type'] === 'painting'
                        ? $painting->title
                        : $video->title,
                    'description' => $validated['type'] === 'painting'
                        ? "Original painting by {$painting->user->name}"
                        : "Video tutorial by {$video->user->name}",
                ],
                'unit_amount' => (int) ($subtotal * 100),
            ],
            'quantity' => 1,
        ];

        $params = [
            'payment_method_types' => ['card'],
            'line_items' => [$lineItem],
            'mode' => 'payment',
            'success_url' => config('app.url') . "/orders/{$order->order_number}?success=1",
            'cancel_url' => config('app.url') . "/orders/{$order->order_number}?cancelled=1",
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'buyer_id' => $request->user()->id,
                'painter_id' => $painterId,
                'type' => $validated['type'],
            ],
            'payment_intent_data' => [
                'application_fee_amount' => (int) ($commissionAmount * 100),
                'transfer_data' => [
                    'destination' => $painter->stripe_account_id ?? $painterId,
                ],
                'metadata' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                ],
            ],
        ];

        $session = $stripe->checkout->sessions->create($params);

        $order->update(['stripe_session_id' => $session->id]);

        return response()->json([
            'url' => $session->url,
            'order_number' => $order->order_number,
        ], 201);
    }

    public function index(Request $request): JsonResponse
    {
        $orders = Order::with(['painting', 'video', 'painter'])
            ->where('buyer_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, string $id): JsonResponse
    {
        $order = Order::with(['painting.images', 'video', 'painter', 'buyer'])
            ->where(function ($q) use ($request) {
                $q->where('buyer_id', $request->user()->id)
                  ->orWhere('painter_id', $request->user()->id);
            })
            ->where('order_number', $id)
            ->firstOrFail();

        return response()->json($order);
    }

    public function painterOrders(Request $request): JsonResponse
    {
        $orders = Order::with(['painting', 'video', 'buyer'])
            ->where('painter_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function earnings(Request $request): JsonResponse
    {
        $user = $request->user();

        $totalEarnings = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->sum('payout_amount');

        $pendingEarnings = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('stripe_transfer_id')
            ->sum('payout_amount');

        $monthlyEarnings = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->whereMonth('paid_at', now()->month)
            ->whereYear('paid_at', now()->year)
            ->sum('payout_amount');

        $totalSales = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $recentOrders = Order::with('buyer')
            ->where('painter_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('paid_at', 'desc')
            ->limit(5)
            ->get();

        $monthlyData = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->where('paid_at', '>=', now()->subMonths(12)->startOfMonth())
            ->selectRaw("strftime('%Y-%m', paid_at) as month, sum(payout_amount) as earnings, count(*) as sales")
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return response()->json([
            'total_earnings' => $totalEarnings,
            'pending_earnings' => $pendingEarnings,
            'monthly_earnings' => $monthlyEarnings,
            'total_sales' => $totalSales,
            'recent_orders' => $recentOrders,
            'monthly_data' => $monthlyData,
        ]);
    }

    public function webhook(Request $request): JsonResponse
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        $stripe = new StripeClient(config('services.stripe.secret'));

        try {
            $event = $stripe->webhooks->constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;

            case 'charge.refunded':
                $this->handleRefund($event->data->object);
                break;
        }

        return response()->json(['received' => true]);
    }

    private function handleCheckoutCompleted($session): void
    {
        $orderId = $session->metadata->order_id ?? null;
        if (!$orderId) return;

        $order = Order::find($orderId);
        if (!$order || $order->status === 'completed') return;

        $order->markPaid(
            $session->payment_intent,
            $session->payment_intent
        );

        if ($order->type === 'video' && $order->video_id) {
            VideoPurchase::updateOrCreate(
                [
                    'user_id' => $order->buyer_id,
                    'video_id' => $order->video_id,
                ],
                [
                    'amount' => $order->total,
                    'currency' => $order->currency,
                    'stripe_payment_intent_id' => $session->payment_intent,
                    'status' => 'completed',
                ]
            );
        }
    }

    private function handlePaymentFailed($paymentIntent): void
    {
        $order = Order::where('stripe_session_id', $paymentIntent->metadata->order_number ?? '')
            ->orWhere('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($order) {
            $order->update(['status' => 'failed']);
        }
    }

    private function handleRefund($charge): void
    {
        $paymentIntentId = $charge->payment_intent;
        $order = Order::where('stripe_payment_intent_id', $paymentIntentId)->first();

        if ($order) {
            $order->update([
                'status' => 'refunded',
                'refunded_at' => now(),
            ]);

            if ($order->painting) {
                $order->painting->update(['is_available' => true]);
            }
        }
    }
}

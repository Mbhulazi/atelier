# Skill 08: Marketplace Payments

## Overview
Implement Stripe Connect for marketplace payments (painters receive directly) and Stripe Standard mode. Handle commission tracking, payouts, and purchase receipts.

---

## Step 1: Payment Service

### `app/Services/PaymentService.php`

```php
<?php

namespace App\Services;

use App\Models\Order;
use App\Models\User;
use App\Models\Painting;
use App\Models\Video;
use Illuminate\Support\Facades\DB;
use Stripe\StripeClient;
use Stripe\Exception\ApiErrorException;

class PaymentService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createCheckoutSession(
        User $buyer,
        string $type,
        int $itemId,
        ?string $successUrl = null,
        ?string $cancelUrl = null
    ): array {
        $item = $this->getItem($type, $itemId);
        $painter = $item->user;

        // Calculate amounts
        $amount = $this->getItemPrice($item);
        $commissionRate = config('services.stripe.commission_rate', 15);
        $commissionAmount = round($amount * ($commissionRate / 100), 2);
        $netAmount = $amount - $commissionAmount;

        // Create order
        $order = Order::create([
            'order_number' => $this->generateOrderNumber(),
            'buyer_id' => $buyer->id,
            'painter_id' => $painter->id,
            'type' => $type,
            'item_id' => $itemId,
            'amount' => $amount,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'net_amount' => $netAmount,
            'status' => 'pending',
        ]);

        // Create Stripe Checkout Session
        $sessionParams = [
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => strtolower($item->currency ?? 'USD'),
                    'product_data' => [
                        'name' => $item->title,
                    ],
                    'unit_amount' => (int)($amount * 100), // cents
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $successUrl ?? url('/orders/{$order->order_number}/success'),
            'cancel_url' => $cancelUrl ?? url('/orders/{$order->order_number}/cancel'),
            'metadata' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'buyer_id' => $buyer->id,
                'painter_id' => $painter->id,
                'type' => $type,
                'item_id' => $itemId,
            ],
        ];

        // Use Stripe Connect if painter has connected account
        if ($painter->stripe_account_id && $painter->stripe_mode === 'connect') {
            $sessionParams['payment_intent_data'] = [
                'application_fee_amount' => (int)($commissionAmount * 100),
                'transfer_data' => [
                    'destination' => $painter->stripe_account_id,
                ],
            ];
        }

        $session = $this->stripe->checkout->sessions->create($sessionParams);

        // Update order with Stripe session
        $order->update([
            'stripe_payment_intent_id' => $session->payment_intent,
        ]);

        return [
            'session_id' => $session->id,
            'url' => $session->url,
        ];
    }

    public function handleWebhook(array $payload, string $sigHeader): void
    {
        $event = \Stripe\Webhook::constructEvent(
            json_encode($payload),
            $sigHeader,
            config('services.stripe.webhook_secret')
        );

        switch ($event->type) {
            case 'checkout.session.completed':
                $this->handleCheckoutCompleted($event->data->object);
                break;

            case 'payment_intent.succeeded':
                $this->handlePaymentSucceeded($event->data->object);
                break;

            case 'payment_intent.payment_failed':
                $this->handlePaymentFailed($event->data->object);
                break;
        }
    }

    protected function handleCheckoutCompleted($session): void
    {
        $order = Order::where('stripe_payment_intent_id', $session->payment_intent)
            ->firstOrFail();

        $order->update([
            'status' => 'completed',
            'paid_at' => now(),
            'stripe_charge_id' => $session->payment_intent,
        ]);

        // Create purchase record
        \App\Models\Purchase::create([
            'user_id' => $order->buyer_id,
            'purchasable_type' => $this->getModelClass($order->type),
            'purchasable_id' => $order->item_id,
            'order_id' => $order->id,
        ]);

        // Update item counts
        $this->incrementPurchaseCount($order->type, $order->item_id);

        // Send receipt email
        \App\Jobs\SendPurchaseReceipt::dispatch($order);
    }

    protected function handlePaymentSucceeded($paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($order && $order->status !== 'completed') {
            $order->update(['status' => 'completed', 'paid_at' => now()]);
        }
    }

    protected function handlePaymentFailed($paymentIntent): void
    {
        $order = Order::where('stripe_payment_intent_id', $paymentIntent->id)
            ->first();

        if ($order) {
            $order->update(['status' => 'failed']);
        }
    }

    protected function getItem(string $type, int $id)
    {
        return match ($type) {
            'painting' => Painting::findOrFail($id),
            'video' => Video::findOrFail($id),
            default => throw new \InvalidArgumentException("Invalid item type: {$type}"),
        };
    }

    protected function getItemPrice($item): float
    {
        return (float) $item->price;
    }

    protected function getModelClass(string $type): string
    {
        return match ($type) {
            'painting' => Painting::class,
            'video' => Video::class,
            default => throw new \InvalidArgumentException("Invalid type: {$type}"),
        };
    }

    protected function incrementPurchaseCount(string $type, int $itemId): void
    {
        match ($type) {
            'video' => Video::where('id', $itemId)->increment('purchase_count'),
            default => null,
        };
    }

    protected function generateOrderNumber(): string
    {
        $prefix = 'ORD-' . date('Y') . '-';
        $lastOrder = Order::where('order_number', 'like', $prefix . '%')
            ->orderBy('id', 'desc')
            ->first();

        if ($lastOrder) {
            $lastNumber = (int) str_replace($prefix, '', $lastOrder->order_number);
            return $prefix . str_pad($lastNumber + 1, 5, '0', STR_PAD_LEFT);
        }

        return $prefix . '00001';
    }
}
```

---

## Step 2: Stripe Connect Service

### `app/Services/StripeConnectService.php`

```php
<?php

namespace App\Services;

use App\Models\User;
use Stripe\StripeClient;

class StripeConnectService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('services.stripe.secret'));
    }

    public function createConnectAccount(User $user): array
    {
        $account = $this->stripe->accounts->create([
            'type' => 'express',
            'email' => $user->email,
            'capabilities' => [
                'card_payments' => ['requested' => true],
                'transfers' => ['requested' => true],
            ],
            'metadata' => [
                'user_id' => $user->id,
            ],
        ]);

        $user->update([
            'stripe_account_id' => $account->id,
            'stripe_mode' => 'connect',
        ]);

        return $account->toArray();
    }

    public function createAccountLink(User $user, string $refreshUrl, string $returnUrl): string
    {
        $accountLink = $this->stripe->accountLinks->create([
            'account' => $user->stripe_account_id,
            'refresh_url' => $refreshUrl,
            'return_url' => $returnUrl,
            'type' => 'account_onboarding',
        ]);

        return $accountLink->url;
    }

    public function getAccountStatus(User $user): array
    {
        if (!$user->stripe_account_id) {
            return [
                'connected' => false,
                'details_submitted' => false,
                'charges_enabled' => false,
                'payouts_enabled' => false,
            ];
        }

        $account = $this->stripe->accounts->retrieve($user->stripe_account_id);

        return [
            'connected' => $account->charges_enabled && $account->payouts_enabled,
            'details_submitted' => $account->details_submitted,
            'charges_enabled' => $account->charges_enabled,
            'payouts_enabled' => $account->payouts_enabled,
        ];
    }

    public function getBalance(User $user): array
    {
        if (!$user->stripe_account_id) {
            return ['available' => 0, 'pending' => 0];
        }

        $balance = $this->stripe->balance->retrieve([], [
            'stripe_account' => $user->stripe_account_id,
        ]);

        return [
            'available' => collect($balance->available)->sum('amount') / 100,
            'pending' => collect($balance->pending)->sum('amount') / 100,
        ];
    }

    public function getRecentPayouts(User $user, int $limit = 10): array
    {
        if (!$user->stripe_account_id) {
            return [];
        }

        $payouts = $this->stripe->payouts->all([
            'limit' => $limit,
        ], [
            'stripe_account' => $user->stripe_account_id,
        ]);

        return collect($payouts->data)->map(function ($payout) {
            return [
                'id' => $payout->id,
                'amount' => $payout->amount / 100,
                'currency' => strtoupper($payout->currency),
                'status' => $payout->status,
                'arrival_date' => \Carbon\Carbon::createFromTimestamp($payout->arrival_date),
                'type' => $payout->type,
            ];
        })->toArray();
    }
}
```

---

## Step 3: Order Controller (Updated)

### `app/Http/Controllers/Api/OrderController.php`

```php
<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(
        protected PaymentService $paymentService
    ) {}

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:painting,video',
            'item_id' => 'required|integer',
        ]);

        $result = $this->paymentService->createCheckoutSession(
            buyer: $request->user(),
            type: $validated['type'],
            itemId: $validated['item_id'],
            successUrl: $request->input('success_url'),
            cancelUrl: $request->input('cancel_url'),
        );

        return response()->json($result);
    }

    public function index(Request $request)
    {
        $orders = Order::where('buyer_id', $request->user()->id)
            ->with('painter')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function show(Request $request, $id)
    {
        $order = Order::where('buyer_id', $request->user()->id)
            ->with(['painter', 'orderItems'])
            ->findOrFail($id);

        return response()->json($order);
    }

    public function painterOrders(Request $request)
    {
        $orders = Order::where('painter_id', $request->user()->id)
            ->with('buyer')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return response()->json($orders);
    }

    public function earnings(Request $request)
    {
        $user = $request->user();

        $earnings = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->selectRaw('
                SUM(net_amount) as total_earned,
                COUNT(*) as total_orders,
                AVG(net_amount) as avg_order_value
            ')
            ->first();

        $monthlyEarnings = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->where('paid_at', '>=', now()->startOfMonth())
            ->sum('net_amount');

        $pendingPayouts = Order::where('painter_id', $user->id)
            ->where('status', 'completed')
            ->whereNull('stripe_transfer_id')
            ->sum('net_amount');

        return response()->json([
            'total_earned' => $earnings->total_earned ?? 0,
            'total_orders' => $earnings->total_orders ?? 0,
            'avg_order_value' => $earnings->avg_order_value ?? 0,
            'monthly_earned' => $monthlyEarnings,
            'pending_payout' => $pendingPayouts,
        ]);
    }

    public function webhook(Request $request)
    {
        $payload = $request->all();
        $sigHeader = $request->header('Stripe-Signature');

        $this->paymentService->handleWebhook($payload, $sigHeader);

        return response()->json(['status' => 'success']);
    }
}
```

---

## Step 4: Stripe Config

### `config/services.php` (additions)

```php
<?php

return [
    // ... existing config ...

    'stripe' => [
        'key' => env('STRIPE_KEY'),
        'secret' => env('STRIPE_SECRET'),
        'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
        'connect_client_id' => env('STRIPE_CONNECT_CLIENT_ID'),
        'commission_rate' => env('STRIPE_COMMISSION_RATE', 15),
    ],
];
```

---

## Step 5: Nuxt Stripe Plugin

### `plugins/stripe.client.ts`

```typescript
import { loadStripe, type Stripe } from '@stripe/stripe-js'

let stripe: Stripe | null = null

export default defineNuxtPlugin(async () => {
  const config = useRuntimeConfig()

  if (config.public.stripeKey) {
    stripe = await loadStripe(config.public.stripeKey)
  }

  return {
    provide: {
      stripe,
    },
  }
})
```

### `composables/useStripe.ts`

```typescript
export const useStripe = () => {
  const { $stripe } = useNuxtApp()

  const checkout = async (sessionId: string) => {
    if (!$stripe) {
      throw new Error('Stripe not initialized')
    }

    const { error } = await $stripe.redirectToCheckout({ sessionId })

    if (error) {
      throw error
    }
  }

  const createCheckoutSession = async (type: string, itemId: number) => {
    const config = useRuntimeConfig()

    const response = await $fetch<{ session_id: string; url: string }>(
      `${config.public.apiUrl}/orders/checkout`,
      {
        method: 'POST',
        body: { type, item_id: itemId },
        withCredentials: true,
      }
    )

    return response
  }

  return {
    checkout,
    createCheckoutSession,
  }
}
```

---

## Step 6: Payment Success Page

### `pages/orders/[orderNumber]/success.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20 pb-16">
    <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 py-12 text-center">
      <div class="w-20 h-20 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-6">
        <Icon name="mdi:check-circle" class="w-12 h-12 text-green-600" />
      </div>

      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-4">
        Payment Successful!
      </h1>

      <p class="text-lg text-canvas-600 mb-8">
        Your order <span class="font-mono font-medium">{{ orderNumber }}</span> has been confirmed.
      </p>

      <div class="flex flex-wrap justify-center gap-4">
        <NuxtLink
          to="/dashboard/orders"
          class="px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition"
        >
          View Orders
        </NuxtLink>
        <NuxtLink
          to="/shop"
          class="px-6 py-3 border border-canvas-300 rounded-lg font-medium text-canvas-700 hover:border-primary-400 transition"
        >
          Continue Shopping
        </NuxtLink>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
const route = useRoute()
const orderNumber = computed(() => route.params.orderNumber)
</script>
```

---

## Step 7: Painter Stripe Settings

### `pages/dashboard/settings.vue`

```vue
<template>
  <div class="min-h-screen bg-canvas-50 pt-20">
    <div class="max-w-3xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
      <h1 class="text-3xl font-serif font-bold text-canvas-900 mb-8">Payment Settings</h1>

      <!-- Stripe Connect Status -->
      <div class="bg-white rounded-2xl shadow-sm p-6 mb-6">
        <h2 class="text-lg font-semibold text-canvas-900 mb-4">Stripe Account</h2>

        <div v-if="stripeStatus.connected" class="flex items-center gap-4 p-4 bg-green-50 rounded-xl">
          <Icon name="mdi:check-circle" class="w-8 h-8 text-green-600" />
          <div>
            <p class="font-medium text-green-800">Account Connected</p>
            <p class="text-sm text-green-600">You're ready to receive payments directly.</p>
          </div>
        </div>

        <div v-else class="space-y-4">
          <p class="text-canvas-600">
            Connect your Stripe account to receive payments directly. Or use Standard mode where we handle payouts.
          </p>

          <div class="flex gap-4">
            <button
              @click="connectStripe"
              :disabled="connecting"
              class="px-6 py-3 bg-primary-600 text-white rounded-lg font-medium hover:bg-primary-700 transition disabled:opacity-50"
            >
              {{ connecting ? 'Connecting...' : 'Connect with Stripe' }}
            </button>

            <button
              @click="useStandardMode"
              class="px-6 py-3 border border-canvas-300 rounded-lg font-medium text-canvas-700 hover:border-primary-400 transition"
            >
              Use Standard Mode
            </button>
          </div>
        </div>
      </div>

      <!-- Earnings Summary -->
      <div class="bg-white rounded-2xl shadow-sm p-6">
        <h2 class="text-lg font-semibold text-canvas-900 mb-4">Earnings</h2>

        <div class="grid grid-cols-2 gap-6">
          <div>
            <p class="text-sm text-canvas-500">Total Earned</p>
            <p class="text-2xl font-bold text-canvas-900">${{ earnings.total_earned }}</p>
          </div>
          <div>
            <p class="text-sm text-canvas-500">This Month</p>
            <p class="text-2xl font-bold text-canvas-900">${{ earnings.monthly_earned }}</p>
          </div>
          <div>
            <p class="text-sm text-canvas-500">Pending Payout</p>
            <p class="text-2xl font-bold text-primary-600">${{ earnings.pending_payout }}</p>
          </div>
          <div>
            <p class="text-sm text-canvas-500">Total Orders</p>
            <p class="text-2xl font-bold text-canvas-900">{{ earnings.total_orders }}</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup lang="ts">
definePageMeta({ middleware: 'painter' })

const config = useRuntimeConfig()
const connecting = ref(false)

const { data: statusData } = await useFetch<any>(
  `${config.public.apiUrl}/stripe/connect/status`,
  { withCredentials: true }
)

const stripeStatus = computed(() => statusData.value || { connected: false })

const { data: earningsData } = await useFetch<any>(
  `${config.public.apiUrl}/dashboard/earnings`,
  { withCredentials: true }
)

const earnings = computed(() => earningsData.value || {
  total_earned: '0.00',
  monthly_earned: '0.00',
  pending_payout: '0.00',
  total_orders: 0,
})

const connectStripe = async () => {
  connecting.value = true
  try {
    const { url } = await $fetch<{ url: string }>(
      `${config.public.apiUrl}/stripe/connect/onboard`,
      { method: 'POST', withCredentials: true }
    )
    window.location.href = url
  } catch (error) {
    console.error('Stripe connect failed:', error)
  } finally {
    connecting.value = false
  }
}

const useStandardMode = async () => {
  await $fetch(
    `${config.public.apiUrl}/stripe/connect/standard`,
    { method: 'POST', withCredentials: true }
  )
  window.location.reload()
}
</script>
```

---

## Verification Checklist

- [ ] Checkout session creates successfully
- [ ] Stripe redirect works
- [ ] Webhook processes payment
- [ ] Order status updates to completed
- [ ] Purchase record created
- [ ] Painter earnings calculated correctly
- [ ] Stripe Connect onboarding works
- [ ] Commission deducted correctly

---

## Next Steps

Proceed to [Skill 09: Admin Panel](./09-admin-panel.md) for platform management.

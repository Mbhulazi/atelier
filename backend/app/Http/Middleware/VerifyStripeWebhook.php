<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Stripe\Webhook;

class VerifyStripeWebhook
{
    public function handle(Request $request, Closure $next)
    {
        $payload = $request->getContent();
        $sigHeader = $request->header('Stripe-Signature');

        try {
            Webhook::constructEvent(
                $payload,
                $sigHeader,
                config('services.stripe.webhook_secret')
            );
        } catch (\Exception $e) {
            return response()->json(['error' => 'Invalid signature'], 400);
        }

        return $next($request);
    }
}

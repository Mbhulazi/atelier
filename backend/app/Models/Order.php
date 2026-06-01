<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'buyer_id',
        'painter_id',
        'painting_id',
        'video_id',
        'type',
        'subtotal',
        'commission_rate',
        'commission_amount',
        'payout_amount',
        'total',
        'currency',
        'stripe_session_id',
        'stripe_payment_intent_id',
        'stripe_transfer_id',
        'status',
        'shipping_address',
        'notes',
        'metadata',
        'paid_at',
        'shipped_at',
        'delivered_at',
        'refunded_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'commission_rate' => 'decimal:4',
        'commission_amount' => 'decimal:2',
        'payout_amount' => 'decimal:2',
        'total' => 'decimal:2',
        'metadata' => 'array',
        'paid_at' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'refunded_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($order) {
            if (!$order->order_number) {
                $order->order_number = self::generateOrderNumber();
            }
        });
    }

    public static function generateOrderNumber(): string
    {
        $prefix = 'ATL';
        $date = now()->format('ymd');
        $random = strtoupper(substr(uniqid(), -5));
        return "{$prefix}-{$date}-{$random}";
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function painter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'painter_id');
    }

    public function painting(): BelongsTo
    {
        return $this->belongsTo(Painting::class);
    }

    public function video(): BelongsTo
    {
        return $this->belongsTo(Video::class);
    }

    public function markPaid(string $paymentIntentId, string $transferId = null): void
    {
        $this->update([
            'status' => 'completed',
            'stripe_payment_intent_id' => $paymentIntentId,
            'stripe_transfer_id' => $transferId,
            'paid_at' => now(),
        ]);

        if ($this->painting) {
            $this->painting->update(['is_available' => false]);
        }
    }

    public function getCommissionPercentageAttribute(): float
    {
        return $this->commission_rate * 100;
    }
}

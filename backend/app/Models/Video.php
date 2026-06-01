<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Video extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'video_url',
        'thumbnail_url',
        'duration',
        'price',
        'currency',
        'category',
        'difficulty',
        'tags',
        'is_free',
        'is_published',
        'is_featured',
        'views_count',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_free' => 'boolean',
        'is_published' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function purchases()
    {
        return $this->hasMany(VideoPurchase::class);
    }

    public function incrementViews(): void
    {
        $this->increment('views_count');
    }

    public function getFormattedDurationAttribute(): ?string
    {
        if (!$this->duration) return null;
        $m = floor($this->duration / 60);
        $s = $this->duration % 60;
        return sprintf('%d:%02d', $m, $s);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Painting extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'medium',
        'style',
        'width',
        'height',
        'depth',
        'price',
        'currency',
        'is_available',
        'is_featured',
        'year_created',
        'tags',
        'metadata',
    ];

    protected $casts = [
        'tags' => 'array',
        'metadata' => 'array',
        'is_available' => 'boolean',
        'is_featured' => 'boolean',
        'price' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'depth' => 'decimal:2',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(PaintingImage::class)->orderBy('sort_order');
    }

    public function primaryImage()
    {
        return $this->hasOne(PaintingImage::class)->where('is_primary', true);
    }

    public function getDimensionsAttribute(): ?string
    {
        if ($this->width && $this->height) {
            $dims = "{$this->width} × {$this->height} cm";
            if ($this->depth) {
                $dims .= " × {$this->depth} cm";
            }
            return $dims;
        }
        return null;
    }
}

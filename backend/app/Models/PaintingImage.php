<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaintingImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'painting_id',
        'image_url',
        'thumbnail_url',
        'sort_order',
        'is_primary',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
    ];

    public function painting(): BelongsTo
    {
        return $this->belongsTo(Painting::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PainterProfile extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'slug',
        'specialties',
        'style',
        'location',
        'website',
        'featured_image',
        'is_featured',
        'featured_order',
        'social_links',
        'settings',
    ];

    protected $casts = [
        'specialties' => 'array',
        'style' => 'array',
        'social_links' => 'array',
        'settings' => 'array',
        'is_featured' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

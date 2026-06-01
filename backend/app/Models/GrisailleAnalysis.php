<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GrisailleAnalysis extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'original_image_url',
        'processed_image_url',
        'value_map',
        'value_percentages',
        'palette_recommendation',
        'glazing_suggestions',
        'pdf_url',
        'image_width',
        'image_height',
        'total_pixels',
        'metadata',
    ];

    protected $casts = [
        'value_map' => 'array',
        'value_percentages' => 'array',
        'palette_recommendation' => 'array',
        'glazing_suggestions' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

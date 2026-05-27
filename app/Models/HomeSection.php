<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeSection extends Model
{
    protected $fillable = [
        'section_type',
        'display_style',
        'category_id',
        'product_id',
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_url',
        'image',
        'image_2',
        'image_3',
        'image_position',
        'auto_slide',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'auto_slide' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
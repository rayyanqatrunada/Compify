<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class AboutSection extends Model
{
    public const TYPE_HERO = 'about_hero';
    public const TYPE_INTRO = 'about_intro';
    public const TYPE_STATS = 'about_stats';
    public const TYPE_QUOTE = 'about_quote';
    public const TYPE_VALUE = 'about_value';
    public const TYPE_BANNER = 'about_banner';
    public const TYPE_HISTORY = 'about_history';
    public const TYPE_TESTIMONIAL = 'about_testimonial';

    public const TYPES = [
        self::TYPE_HERO,
        self::TYPE_INTRO,
        self::TYPE_STATS,
        self::TYPE_QUOTE,
        self::TYPE_VALUE,
        self::TYPE_BANNER,
        self::TYPE_HISTORY,
        self::TYPE_TESTIMONIAL,
    ];

    protected $fillable = [
        'section_type',
        'title',
        'subtitle',
        'description',
        'button_text',
        'button_url',
        'image',
        'stat_value',
        'icon',
        'year',
        'rating',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'rating' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('section_type', $type);
    }
}
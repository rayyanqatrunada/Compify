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

    public const TYPES = [
        self::TYPE_HERO,
        self::TYPE_INTRO,
        self::TYPE_STATS,
        self::TYPE_QUOTE,
        self::TYPE_VALUE,
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
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
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

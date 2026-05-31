<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EventHeroImage extends Model
{
    public const POSITION_MAIN = 'main';
    public const POSITION_SIDE_TOP = 'side_top';
    public const POSITION_SIDE_BOTTOM = 'side_bottom';

    protected $fillable = [
        'position',
        'title',
        'subtitle',
        'image',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public static function positions(): array
    {
        return [
            self::POSITION_MAIN => 'Hero Besar Kiri',
            self::POSITION_SIDE_TOP => 'Hero Kanan Atas',
            self::POSITION_SIDE_BOTTOM => 'Hero Kanan Bawah',
        ];
    }
}
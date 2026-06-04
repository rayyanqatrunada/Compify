<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomeLayoutSlot extends Model
{
    protected $fillable = [
        'home_layout_group_id',
        'slot_number',
        'slot_type',
        'product_source',
        'category_id',
        'home_section_id',
        'title',
        'subtitle',
        'is_active',
    ];

    protected $casts = [
        'slot_number' => 'integer',
        'is_active' => 'boolean',
    ];

    public const TYPE_NONE = 'none';
    public const TYPE_PRODUCT_DISPLAY = 'product_display';
    public const TYPE_FULL_BANNER = 'full_banner';
    public const TYPE_SPLIT_BANNER = 'split_banner';
    public const TYPE_GALLERY = 'gallery';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_NONE => 'Kosong',
            self::TYPE_PRODUCT_DISPLAY => 'Display Produk',
            self::TYPE_FULL_BANNER => 'Full Banner',
            self::TYPE_SPLIT_BANNER => 'Split Banner',
            self::TYPE_GALLERY => 'Gallery 3 Images',
        ];
    }

    public const SOURCE_CATEGORY = 'category';
    public const SOURCE_BEST_SELLER = 'best_seller';
    public const SOURCE_LATEST = 'latest';

    public static function productSourceOptions(): array
    {
        return [
            self::SOURCE_CATEGORY => 'Kategori',
            self::SOURCE_BEST_SELLER => 'Best Seller',
            self::SOURCE_LATEST => 'Latest Product',
        ];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(HomeLayoutGroup::class, 'home_layout_group_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function homeSection(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class);
    }

    public function getTypeLabelAttribute(): string
    {
        return self::typeOptions()[$this->slot_type] ?? 'Kosong';
    }
}
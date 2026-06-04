<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class HomeCategoryGridSetting extends Model
{
    protected $fillable = [
        'title',
        'subtitle',
        'columns_desktop',
        'columns_tablet',
        'columns_mobile',
        'is_active',
    ];

    protected $casts = [
        'columns_desktop' => 'integer',
        'columns_tablet' => 'integer',
        'columns_mobile' => 'integer',
        'is_active' => 'boolean',
    ];

    public static function current(): self
    {
        return static::query()->firstOrCreate([], [
            'title' => 'Kategori Pilihan',
            'subtitle' => 'Pilih kategori produk sesuai kebutuhan build PC Anda.',
            'columns_desktop' => 6,
            'columns_tablet' => 4,
            'columns_mobile' => 2,
            'is_active' => true,
        ]);
    }
}
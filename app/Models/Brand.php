<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Brand extends Model
{
    protected $fillable = [
        'name', 'slug', 'logo', 'website_url', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    // ── Relasi ──────────────────────────────────────────────────────────────

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    // ── Scope ───────────────────────────────────────────────────────────────

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // ── Accessor ─────────────────────────────────────────────────────────────

    /**
     * Kembalikan URL logo jika ada, null jika tidak.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return $this->logo ? Storage::url($this->logo) : null;
    }

    /**
     * Inisial 2 huruf dari nama brand (fallback tampilan tanpa logo).
     */
    public function getInitialsAttribute(): string
    {
        return strtoupper(substr($this->name, 0, 2));
    }

    // ── Mutator ──────────────────────────────────────────────────────────────

    /**
     * Auto-generate slug dari name jika slug kosong.
     */
    public function setNameAttribute(string $value): void
    {
        $this->attributes['name'] = $value;

        if (empty($this->attributes['slug'])) {
            $this->attributes['slug'] = Str::slug($value);
        }
    }
}
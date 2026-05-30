<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Category extends Model
{
    protected $fillable = [
        'parent_id',
        'name',
        'slug',
        'image',
        'description',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function selfAndActiveDescendantIds(): array
    {
        return self::activeDescendantIds([$this->id]);
    }

    public static function searchIdsWithActiveDescendants(string $keyword): array
    {
        $keyword = trim($keyword);

        if ($keyword === '') {
            return [];
        }

        $like = '%' . $keyword . '%';

        $matchedIds = self::query()
            ->active()
            ->where(function ($query) use ($like) {
                $query->where('name', 'like', $like)
                    ->orWhere('slug', 'like', $like);
            })
            ->pluck('id')
            ->all();

        if ($matchedIds === []) {
            return [];
        }

        return self::activeDescendantIds($matchedIds);
    }

    private static function activeDescendantIds(array $startingIds): array
    {
        $ids = collect($startingIds)
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        $frontier = $ids;

        while ($frontier->isNotEmpty()) {
            $children = self::query()
                ->active()
                ->whereIn('parent_id', $frontier->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->diff($ids)
                ->values();

            if ($children->isEmpty()) {
                break;
            }

            $ids = $ids->merge($children)->unique()->values();
            $frontier = $children;
        }

        return $ids->all();
    }

    public function navigationGroup(): Collection
    {
        if ($this->parent_id) {
            return self::query()
                ->active()
                ->where(function ($query) {
                    $query->where('id', $this->parent_id)
                        ->orWhere('parent_id', $this->parent_id);
                })
                ->orderByRaw('id = ? desc', [$this->parent_id])
                ->orderBy('sort_order')
                ->get();
        }

        return self::query()
            ->active()
            ->where(function ($query) {
                $query->where('id', $this->id)
                    ->orWhere('parent_id', $this->id);
            })
            ->orderByRaw('id = ? desc', [$this->id])
            ->orderBy('sort_order')
            ->get();
    }
}
<?php

namespace App\Support;

use App\Models\Category;

class CategoryTree
{
    public static function ids(Category|int|null $category): array
    {
        if (! $category) {
            return [];
        }

        if (is_int($category)) {
            $category = Category::find($category);
        }

        if (! $category) {
            return [];
        }

        $ids = [$category->id];

        $children = Category::query()
            ->where('parent_id', $category->id)
            ->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, self::ids($child));
        }

        return array_values(array_unique($ids));
    }
}
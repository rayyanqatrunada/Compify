<?php

namespace App\Imports;

use App\Exports\ProductsExport;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ProductsImport implements ToCollection, WithHeadingRow
{
    protected array $fields;
    protected string $mode;

    public function __construct(array $fields = [], string $mode = 'upsert')
    {
        $this->fields = ProductsExport::normalizeFields($fields);
        $this->mode = $mode;
    }

    public function collection(Collection $rows): void
    {
        foreach ($rows as $row) {
            $name = trim((string) ($row['name'] ?? ''));

            if ($name === '') {
                continue;
            }

            $slug = trim((string) ($row['slug'] ?? '')) ?: Str::slug($name);

            $existingProduct = Product::where('slug', $slug)->first();

            if ($this->mode === 'create_only' && $existingProduct) {
                continue;
            }

            if ($this->mode === 'update_only' && ! $existingProduct) {
                continue;
            }

            $categoryName = trim((string) ($row['category_name'] ?? 'Uncategorized'));
            $categorySlug = trim((string) ($row['category_slug'] ?? '')) ?: Str::slug($categoryName);

            $category = Category::updateOrCreate(
                ['slug' => $categorySlug],
                [
                    'name' => $categoryName,
                    'is_active' => true,
                ]
            );

            $payload = [
                'category_id' => $category->id,
                'name' => $name,
                'slug' => $slug,
                'price' => (int) ($row['price'] ?? 0),
                'stock' => (int) ($row['stock'] ?? 0),
                'is_active' => $this->toBool($row['is_active'] ?? true),
                'sort_order' => (int) ($row['sort_order'] ?? 0),
            ];

            if ($this->uses('sku')) {
                $payload['sku'] = $row['sku'] ?? null;
            }

            if ($this->uses('brand_name') || $this->uses('brand_slug')) {
                $brandName = trim((string) ($row['brand_name'] ?? ''));

                if ($brandName !== '') {
                    $brandSlug = trim((string) ($row['brand_slug'] ?? '')) ?: Str::slug($brandName);

                    $brand = Brand::updateOrCreate(
                        ['slug' => $brandSlug],
                        [
                            'name' => $brandName,
                            'is_active' => true,
                        ]
                    );

                    $payload['brand_id'] = $brand->id;
                }
            }

            if ($this->uses('description')) {
                $payload['description'] = $row['description'] ?? null;
            }

            if ($this->uses('sale_price')) {
                $payload['sale_price'] = $this->nullableNumber($row['sale_price'] ?? null);
            }

            if ($this->uses('image')) {
                $payload['image'] = $row['image'] ?? null;
            }

            if ($this->uses('is_featured')) {
                $payload['is_featured'] = $this->toBool($row['is_featured'] ?? false);
            }

            if ($this->uses('is_new')) {
                $payload['is_new'] = $this->toBool($row['is_new'] ?? false);
            }

            Product::updateOrCreate(
                ['slug' => $slug],
                $payload
            );
        }
    }

    private function uses(string $field): bool
    {
        return in_array($field, $this->fields, true);
    }

    private function nullableNumber($value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    private function toBool($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(strtolower((string) $value), ['1', 'true', 'yes', 'ya', 'aktif'], true);
    }
}
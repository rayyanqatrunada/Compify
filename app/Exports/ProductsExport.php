<?php

namespace App\Exports;

use App\Models\Product;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;

class ProductsExport implements FromCollection, WithHeadings, WithMapping, ShouldAutoSize
{
    protected array $fields;

    public function __construct(array $fields = [])
    {
        $this->fields = self::normalizeFields($fields);
    }

    public static function requiredFields(): array
    {
        return [
            'name' => 'Nama Produk',
            'slug' => 'Slug',
            'category_slug' => 'Slug Kategori',
            'category_name' => 'Nama Kategori',
            'price' => 'Harga Normal',
            'stock' => 'Stok',
            'is_active' => 'Status Aktif',
            'sort_order' => 'Urutan',
        ];
    }

    public static function optionalFields(): array
    {
        return [
            'sku' => 'SKU',
            'brand_slug' => 'Slug Brand',
            'brand_name' => 'Nama Brand',
            'description' => 'Deskripsi',
            'sale_price' => 'Harga Diskon',
            'image' => 'Path Gambar',
            'is_featured' => 'Produk Unggulan',
            'is_new' => 'Produk Baru',
            'created_at' => 'Dibuat Pada',
            'updated_at' => 'Diupdate Pada',
        ];
    }

    public static function allFields(): array
    {
        return self::requiredFields() + self::optionalFields();
    }

    public static function normalizeFields(array $fields): array
    {
        $allowed = array_keys(self::allFields());

        $fields = array_values(array_intersect($fields, $allowed));

        return array_values(array_unique(array_merge(
            array_keys(self::requiredFields()),
            $fields
        )));
    }

    public function collection()
    {
        return Product::with(['category', 'brand'])
            ->orderBy('sort_order')
            ->latest()
            ->get();
    }

    public function headings(): array
    {
        return $this->fields;
    }

    public function map($product): array
    {
        return collect($this->fields)
            ->map(fn ($field) => match ($field) {
                'sku' => $product->sku,
                'name' => $product->name,
                'slug' => $product->slug,
                'category_slug' => $product->category?->slug,
                'category_name' => $product->category?->name,
                'brand_slug' => $product->brand?->slug,
                'brand_name' => $product->brand?->name,
                'description' => $product->description,
                'price' => $product->price,
                'sale_price' => $product->sale_price,
                'stock' => $product->stock,
                'image' => $product->image,
                'is_featured' => $product->is_featured ? 1 : 0,
                'is_new' => $product->is_new ? 1 : 0,
                'is_active' => $product->is_active ? 1 : 0,
                'sort_order' => $product->sort_order,
                'created_at' => optional($product->created_at)->format('Y-m-d H:i:s'),
                'updated_at' => optional($product->updated_at)->format('Y-m-d H:i:s'),
                default => null,
            })
            ->toArray();
    }
}
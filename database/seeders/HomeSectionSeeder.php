<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Ambil kategori berdasarkan nama
        |--------------------------------------------------------------------------
        */

        $findCategory = function (array $names) {
            foreach ($names as $name) {
                $category = Category::query()
                    ->where('slug', Str::slug($name))
                    ->orWhere('name', 'like', '%' . $name . '%')
                    ->first();

                if ($category) {
                    return $category;
                }
            }

            return null;
        };

        /*
        |--------------------------------------------------------------------------
        | Ambil produk aktif berdasarkan kategori
        |--------------------------------------------------------------------------
        */

        $findProduct = function (?Category $category = null) {
            $query = Product::with(['category', 'brand'])
                ->active()
                ->orderBy('sort_order')
                ->latest();

            if ($category) {
                $categoryIds = collect([$category->id])
                    ->merge($category->children()->pluck('id'))
                    ->unique()
                    ->values();

                $query->whereIn('category_id', $categoryIds);
            }

            return $query->first() ?? Product::active()->latest()->first();
        };

        $motherboard = $findCategory(['Motherboard']);
        $ram = $findCategory(['RAM', 'Memory']);
        $gpu = $findCategory(['VGA / GPU', 'GPU', 'VGA']);
        $psu = $findCategory(['Power Supply', 'PSU']);
        $storage = $findCategory(['SSD & Storage', 'Storage', 'SSD']);
        $cooling = $findCategory(['Cooling', 'Cooler']);
        $peripheral = $findCategory(['Peripheral', 'Keyboard', 'Mouse', 'Headset']);

        /*
        |--------------------------------------------------------------------------
        | Bersihkan demo home section lama
        |--------------------------------------------------------------------------
        | Ini supaya data lama tidak dobel dengan layout baru.
        | Kalau kamu tidak mau menghapus data yang sudah diatur manual dari admin,
        | hapus bagian delete ini.
        */

        HomeSection::query()
            ->whereBetween('sort_order', [1, 20])
            ->delete();

        /*
        |--------------------------------------------------------------------------
        | Layout Home Sekarang
        |--------------------------------------------------------------------------
        | Setelah hero slider:
        | 1. display product card
        | 2. full image preview
        | 3. display card
        | 4. image kanan/kiri
        | 5. display card
        | 6. image kanan/kiri
        | 7. display card
        | 8. preview 3 gambar
        | 9. display barang
        | 10. display barang
        */

        $sections = [
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $motherboard?->id,
                'product_id' => $findProduct($motherboard)?->id,
                'title' => 'Motherboard Pilihan',
                'subtitle' => 'Komponen utama untuk build PC modern',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'section_type' => 'story',
                'display_style' => 'full_banner',
                'category_id' => null,
                'product_id' => $findProduct($gpu)?->id,
                'title' => 'Build PC Lebih Mudah',
                'subtitle' => 'Compify Featured Setup',
                'description' => 'Temukan komponen komputer terbaik untuk gaming, editing, sekolah, dan kebutuhan produktivitas harian.',
                'button_text' => 'Explore Product',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 2,
                'image' => $this->seedHomeImage('full-banner-1'),
            ],
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $ram?->id,
                'product_id' => $findProduct($ram)?->id,
                'title' => 'RAM & Memory',
                'subtitle' => 'Upgrade performa multitasking',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'section_type' => 'story',
                'display_style' => 'split',
                'category_id' => $psu?->id,
                'product_id' => $findProduct($psu)?->id,
                'title' => 'Power Stabil untuk Setup Kamu',
                'subtitle' => 'Power Supply & Cooling',
                'description' => 'Pilih power supply dan pendinginan yang tepat agar PC lebih stabil, aman, dan siap dipakai untuk kebutuhan harian maupun gaming.',
                'button_text' => 'Learn More',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 4,
                'image' => $this->seedHomeImage('split-1'),
            ],
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $gpu?->id,
                'product_id' => $findProduct($gpu)?->id,
                'title' => 'VGA / GPU',
                'subtitle' => 'Gaming, rendering, dan visual performance',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'section_type' => 'story',
                'display_style' => 'split',
                'category_id' => $storage?->id,
                'product_id' => $findProduct($storage)?->id,
                'title' => 'Storage Cepat, Kerja Lebih Ringan',
                'subtitle' => 'SSD & Storage Upgrade',
                'description' => 'Gunakan SSD dan storage yang sesuai agar booting, loading aplikasi, dan penyimpanan file terasa lebih cepat.',
                'button_text' => 'Explore Storage',
                'button_url' => '/products',
                'image_position' => 'left',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 6,
                'image' => $this->seedHomeImage('split-2'),
            ],
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $storage?->id,
                'product_id' => $findProduct($storage)?->id,
                'title' => 'Storage',
                'subtitle' => 'SSD dan penyimpanan pilihan',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'section_type' => 'gallery',
                'display_style' => 'gallery_3_images',
                'category_id' => $peripheral?->id,
                'product_id' => $findProduct($peripheral)?->id,
                'title' => 'Produk Pilihan Compify',
                'subtitle' => 'Highlight Product',
                'description' => 'Satu produk dengan tiga tampilan gambar: gambar utama besar dan dua gambar detail kecil.',
                'button_text' => 'Learn More',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 8,
                'image' => $this->seedHomeImage('gallery-1-main'),
                'image_2' => $this->seedHomeImage('gallery-1-2'),
                'image_3' => $this->seedHomeImage('gallery-1-3'),
            ],
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $psu?->id,
                'product_id' => $findProduct($psu)?->id,
                'title' => 'Power Supply',
                'subtitle' => 'Daya stabil untuk komponen PC',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 9,
            ],
            [
                'section_type' => 'category_products',
                'display_style' => 'product_grid',
                'category_id' => $cooling?->id,
                'product_id' => $findProduct($cooling)?->id,
                'title' => 'Cooling',
                'subtitle' => 'Suhu lebih aman dan performa lebih stabil',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 10,
            ],
        ];

        foreach ($sections as $section) {
            /*
             * Hapus key image jika file-nya tidak ditemukan,
             * supaya tidak menimpa gambar lama menjadi null.
             */
            $section = array_filter(
                $section,
                fn ($value) => $value !== null
            );

            HomeSection::create($section);
        }
    }

    private function seedHomeImage(string $fileNameWithoutExtension): ?string
    {
        $extensions = ['webp', 'jpg', 'jpeg', 'png'];

        foreach ($extensions as $extension) {
            $sourcePath = public_path("assets/seed/home/{$fileNameWithoutExtension}.{$extension}");

            if (! File::exists($sourcePath)) {
                continue;
            }

            $targetRelativePath = "home-sections/{$fileNameWithoutExtension}.{$extension}";
            $targetFullPath = storage_path("app/public/{$targetRelativePath}");

            File::ensureDirectoryExists(dirname($targetFullPath));
            File::copy($sourcePath, $targetFullPath);

            return $targetRelativePath;
        }

        return null;
    }
}
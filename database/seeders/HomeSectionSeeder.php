<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\HomeSection;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HomeSectionSeeder extends Seeder
{
    public function run(): void
    {
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

        $findProduct = function (?Category $category = null) {
            $query = Product::with(['category', 'brand'])
                ->active()
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

        $mainProduct = Product::where('is_featured', true)->active()->latest()->first()
            ?? Product::active()->latest()->first();

        $sections = [
            [
                'section_type' => 'story',
                'category_id' => null,
                'product_id' => $mainProduct?->id,
                'title' => 'ONIX TOCATA XM2',
                'subtitle' => 'Featured Product',
                'description' => "Tocata XM2 hadir sebagai produk pilihan dengan desain modern, performa tinggi, dan kualitas yang cocok untuk kebutuhan setup komputer premium.\n\nBagian ini bisa kamu ubah dari admin, termasuk teks, tombol, posisi gambar, dan gambar utamanya.",
                'button_text' => 'Order Now!',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'section_type' => 'category_products',
                'category_id' => $motherboard?->id,
                'product_id' => $findProduct($motherboard)?->id,
                'title' => 'Motherboard',
                'subtitle' => 'Komponen PC',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'section_type' => 'story',
                'category_id' => $psu?->id,
                'product_id' => $findProduct($psu)?->id,
                'title' => 'Build Lebih Stabil',
                'subtitle' => 'Power Supply & Cooling',
                'description' => "Pilih power supply dan sistem pendinginan yang tepat agar performa PC lebih stabil, aman, dan siap digunakan untuk kebutuhan harian maupun gaming.\n\nSection ini cocok untuk menampilkan promo PSU, casing, cooling, atau komponen penting lainnya.",
                'button_text' => 'Learn More',
                'button_url' => '/products',
                'image_position' => 'left',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
            [
                'section_type' => 'category_products',
                'category_id' => $ram?->id,
                'product_id' => $findProduct($ram)?->id,
                'title' => 'RAM & Memory',
                'subtitle' => 'Upgrade Performa',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 4,
            ],
            [
                'section_type' => 'story',
                'category_id' => $gpu?->id,
                'product_id' => $findProduct($gpu)?->id,
                'title' => 'Performa Grafis Maksimal',
                'subtitle' => 'GPU & Visual Performance',
                'description' => "Tingkatkan pengalaman gaming, editing, dan pekerjaan visual dengan pilihan GPU yang sesuai kebutuhan.\n\nAdmin bisa mengubah bagian ini untuk menampilkan produk unggulan, promo, atau highlight kategori tertentu.",
                'button_text' => 'Explore GPU',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 5,
            ],
            [
                'section_type' => 'category_products',
                'category_id' => $storage?->id,
                'product_id' => $findProduct($storage)?->id,
                'title' => 'Storage',
                'subtitle' => 'SSD & Penyimpanan',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 6,
            ],
            [
                'section_type' => 'gallery',
                'category_id' => $peripheral?->id,
                'product_id' => $mainProduct?->id,
                'title' => 'Produk Pilihan Compify',
                'subtitle' => 'Highlight Product',
                'description' => 'Gallery section untuk menampilkan satu produk dengan tiga gambar: satu gambar besar dan dua gambar kecil.',
                'button_text' => 'Learn More',
                'button_url' => '/products',
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 7,
            ],
            [
                'section_type' => 'category_products',
                'category_id' => $gpu?->id,
                'product_id' => $findProduct($gpu)?->id,
                'title' => 'VGA / GPU',
                'subtitle' => 'Gaming & Rendering',
                'description' => null,
                'button_text' => null,
                'button_url' => null,
                'image_position' => 'right',
                'auto_slide' => false,
                'is_active' => true,
                'sort_order' => 8,
            ],
            [
                'section_type' => 'category_products',
                'category_id' => $psu?->id,
                'product_id' => $findProduct($psu)?->id,
                'title' => 'Power Supply',
                'subtitle' => 'Daya Stabil',
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
                'category_id' => $cooling?->id,
                'product_id' => $findProduct($cooling)?->id,
                'title' => 'Cooling',
                'subtitle' => 'Suhu Lebih Aman',
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
            HomeSection::updateOrCreate(
                [
                    'section_type' => $section['section_type'],
                    'sort_order' => $section['sort_order'],
                ],
                $section
            );
        }
    }
}
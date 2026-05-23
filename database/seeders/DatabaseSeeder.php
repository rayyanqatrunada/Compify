<?php

namespace Database\Seeders;

use App\Models\Banner;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@compify.test'],
            [
                'name' => 'Admin Compify',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        $categoryNames = [
            'Motherboard',
            'Processor',
            'VGA / GPU',
            'RAM',
            'SSD & Storage',
            'Power Supply',
            'Casing',
            'Cooling',
            'Monitor',
            'Keyboard & Mouse',
        ];

        $groups = [
            'Komponen PC' => [
                'Motherboard',
                'Processor',
                'VGA / GPU',
                'RAM',
                'Power Supply',
                'Casing',
                'Cooling',
            ],
            'Storage' => [
                'SSD & Storage',
                'Hard Disk',
                'External Storage',
            ],
            'Peripheral' => [
                'Monitor',
                'Keyboard',
                'Mouse',
                'Headset',
            ],
            'Aksesoris' => [
                'Cable & Connector',
                'Thermal Paste',
                'Fan Case',
                'Mousepad',
            ],
        ];

        $groupOrder = 1;

        foreach ($groups as $parentName => $children) {
            $parent = Category::updateOrCreate(
                ['slug' => Str::slug($parentName)],
                [
                    'parent_id' => null,
                    'name' => $parentName,
                    'description' => 'Kategori utama ' . $parentName,
                    'is_active' => true,
                    'sort_order' => $groupOrder++,
                ]
            );

            foreach ($children as $index => $childName) {
                Category::updateOrCreate(
                    ['slug' => Str::slug($childName)],
                    [
                        'parent_id' => $parent->id,
                        'name' => $childName,
                        'description' => 'Kategori produk ' . $childName,
                        'is_active' => true,
                        'sort_order' => $index + 1,
                    ]
                );
            }
        }

        foreach ($categoryNames as $index => $name) {
            Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'description' => 'Kategori produk ' . $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        foreach (['ASUS', 'MSI', 'Gigabyte', 'Corsair', 'Cooler Master', 'Kingston'] as $name) {
            Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }

        Banner::updateOrCreate(
            ['title' => 'Build PC Impianmu di Compify'],
            [
                'subtitle' => 'Motherboard, PSU, RAM, SSD, casing, dan perlengkapan komputer pilihan.',
                'button_text' => 'Belanja Sekarang',
                'button_url' => '/products',
                'is_active' => true,
                'sort_order' => 1,
            ]
        );

        $motherboard = Category::where('slug', 'motherboard')->first();
        $psu = Category::where('slug', 'power-supply')->first();
        $asus = Brand::where('slug', 'asus')->first();
        $corsair = Brand::where('slug', 'corsair')->first();

        Product::updateOrCreate(
            ['slug' => 'asus-prime-b760m-a'],
            [
                'category_id' => $motherboard?->id,
                'brand_id' => $asus?->id,
                'sku' => 'MB-ASUS-B760M',
                'name' => 'ASUS Prime B760M-A',
                'description' => 'Motherboard Intel B760 untuk kebutuhan gaming dan produktivitas.',
                'price' => 2450000,
                'sale_price' => 2299000,
                'stock' => 10,
                'is_featured' => true,
                'is_active' => true,
            ]
        );

        Product::updateOrCreate(
            ['slug' => 'corsair-cv650-650w'],
            [
                'category_id' => $psu?->id,
                'brand_id' => $corsair?->id,
                'sku' => 'PSU-CORSAIR-CV650',
                'name' => 'Corsair CV650 650W',
                'description' => 'Power supply 650W untuk PC gaming dan harian.',
                'price' => 850000,
                'sale_price' => null,
                'stock' => 15,
                'is_featured' => true,
                'is_active' => true,
            ]
        );
    }
}
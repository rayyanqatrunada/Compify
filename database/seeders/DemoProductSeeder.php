<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class DemoProductSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Parent Categories
        |--------------------------------------------------------------------------
        */

        $parents = [
            'Komponen PC',
            'Peripheral',
            'Display',
            'Storage',
            'Networking',
        ];

        $parentCategories = [];

        foreach ($parents as $index => $name) {
            $parentCategories[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => null,
                    'name' => $name,
                    'description' => 'Kategori utama ' . $name,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Child Categories
        |--------------------------------------------------------------------------
        */

        $categories = [
            'Motherboard' => 'Komponen PC',
            'Processor' => 'Komponen PC',
            'VGA / GPU' => 'Komponen PC',
            'RAM' => 'Komponen PC',
            'Power Supply' => 'Komponen PC',
            'Casing' => 'Komponen PC',
            'Cooling' => 'Komponen PC',

            'SSD & Storage' => 'Storage',
            'Hard Drive' => 'Storage',

            'Keyboard' => 'Peripheral',
            'Mouse' => 'Peripheral',
            'Headset' => 'Peripheral',
            'Speaker' => 'Peripheral',

            'Monitor' => 'Display',

            'Router' => 'Networking',
            'LAN Cable' => 'Networking',
        ];

        $categoryModels = [];

        foreach ($categories as $name => $parentName) {
            $categoryModels[$name] = Category::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'parent_id' => $parentCategories[$parentName]->id,
                    'name' => $name,
                    'description' => 'Kategori produk ' . $name,
                    'is_active' => true,
                    'sort_order' => count($categoryModels) + 1,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Brands
        |--------------------------------------------------------------------------
        */

        $brandNames = [
            'ASUS',
            'MSI',
            'Gigabyte',
            'Intel',
            'AMD',
            'NVIDIA',
            'Corsair',
            'Kingston',
            'TeamGroup',
            'Samsung',
            'Western Digital',
            'Seagate',
            'Cooler Master',
            'DeepCool',
            'NZXT',
            'Logitech',
            'Razer',
            'Fantech',
            'AOC',
            'TP-Link',
        ];

        $brands = [];

        foreach ($brandNames as $index => $name) {
            $brands[$name] = Brand::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'name' => $name,
                    'is_active' => true,
                ]
            );
        }

        /*
        |--------------------------------------------------------------------------
        | Products
        |--------------------------------------------------------------------------
        */

        $products = [
            // Motherboard
            ['ASUS PRIME B760M-A WIFI DDR5', 'Motherboard', 'ASUS', 2450000, 2299000, 18, true, true],
            ['MSI PRO B650M-P AM5 DDR5', 'Motherboard', 'MSI', 2199000, null, 14, true, false],
            ['Gigabyte B760M DS3H AX DDR5', 'Motherboard', 'Gigabyte', 2350000, 2190000, 20, false, true],
            ['ASUS ROG STRIX B650E-F GAMING WIFI', 'Motherboard', 'ASUS', 5299000, 4999000, 8, true, false],
            ['MSI MAG B760 TOMAHAWK WIFI DDR5', 'Motherboard', 'MSI', 3899000, null, 12, false, false],

            // Processor
            ['Intel Core i5-12400F', 'Processor', 'Intel', 1899000, 1749000, 25, true, false],
            ['Intel Core i5-13400F', 'Processor', 'Intel', 2899000, 2699000, 17, true, true],
            ['Intel Core i7-14700K', 'Processor', 'Intel', 6899000, null, 9, true, false],
            ['AMD Ryzen 5 5600', 'Processor', 'AMD', 1699000, 1549000, 30, true, false],
            ['AMD Ryzen 7 7800X3D', 'Processor', 'AMD', 6999000, 6599000, 11, true, true],
            ['AMD Ryzen 5 7600', 'Processor', 'AMD', 3499000, null, 15, false, true],

            // GPU
            ['ASUS Dual GeForce RTX 4060 8GB', 'VGA / GPU', 'ASUS', 5199000, 4899000, 10, true, true],
            ['MSI GeForce RTX 4060 Ti Ventus 2X 8GB', 'VGA / GPU', 'MSI', 7199000, 6899000, 7, true, false],
            ['Gigabyte Radeon RX 7600 Gaming OC 8GB', 'VGA / GPU', 'Gigabyte', 4699000, null, 13, false, true],
            ['ASUS TUF Gaming RTX 4070 Super 12GB', 'VGA / GPU', 'ASUS', 11299000, 10799000, 5, true, false],
            ['MSI RTX 4070 Ti Super Gaming X Slim', 'VGA / GPU', 'MSI', 15999000, null, 4, true, false],

            // RAM
            ['Kingston Fury Beast 16GB DDR4 3200MHz', 'RAM', 'Kingston', 599000, 549000, 40, true, false],
            ['Kingston Fury Beast 32GB DDR5 5600MHz', 'RAM', 'Kingston', 1699000, 1549000, 22, true, true],
            ['Corsair Vengeance LPX 16GB DDR4 3200MHz', 'RAM', 'Corsair', 629000, null, 35, false, false],
            ['Corsair Vengeance RGB 32GB DDR5 6000MHz', 'RAM', 'Corsair', 1999000, 1849000, 18, true, true],
            ['TeamGroup T-Force Delta RGB 32GB DDR5 6000MHz', 'RAM', 'TeamGroup', 1899000, null, 16, false, true],

            // PSU
            ['Corsair CV650 80+ Bronze', 'Power Supply', 'Corsair', 899000, 829000, 28, true, false],
            ['Cooler Master MWE 750 Bronze V2', 'Power Supply', 'Cooler Master', 1099000, 999000, 21, false, true],
            ['MSI MAG A650BN 650W Bronze', 'Power Supply', 'MSI', 799000, null, 24, false, false],
            ['ASUS TUF Gaming 750W Bronze', 'Power Supply', 'ASUS', 1299000, 1199000, 13, true, false],
            ['Corsair RM850e 80+ Gold Modular', 'Power Supply', 'Corsair', 2299000, null, 9, true, true],

            // Casing
            ['NZXT H5 Flow Black', 'Casing', 'NZXT', 1499000, 1399000, 12, true, false],
            ['Cooler Master MasterBox Q300L', 'Casing', 'Cooler Master', 699000, null, 20, false, false],
            ['DeepCool CH370 White', 'Casing', 'DeepCool', 999000, 929000, 15, false, true],
            ['MSI MAG Forge 120A Airflow', 'Casing', 'MSI', 849000, null, 18, false, false],

            // Cooling
            ['DeepCool AK400 Digital', 'Cooling', 'DeepCool', 599000, 549000, 25, true, true],
            ['Cooler Master Hyper 212 Spectrum V3', 'Cooling', 'Cooler Master', 449000, null, 30, false, false],
            ['NZXT Kraken 240 RGB', 'Cooling', 'NZXT', 2499000, 2299000, 8, true, false],
            ['DeepCool LE520 AIO 240mm', 'Cooling', 'DeepCool', 1399000, null, 11, false, true],

            // SSD & Storage
            ['Samsung 970 EVO Plus 1TB NVMe', 'SSD & Storage', 'Samsung', 1199000, 1099000, 25, true, false],
            ['Samsung 990 EVO 1TB NVMe Gen4', 'SSD & Storage', 'Samsung', 1499000, 1399000, 19, true, true],
            ['WD Blue SN580 1TB NVMe', 'SSD & Storage', 'Western Digital', 1099000, null, 28, false, true],
            ['Kingston NV2 1TB NVMe', 'SSD & Storage', 'Kingston', 899000, 829000, 35, true, false],
            ['Seagate Barracuda 2TB HDD', 'Hard Drive', 'Seagate', 899000, null, 30, false, false],
            ['WD Blue 2TB HDD', 'Hard Drive', 'Western Digital', 879000, 829000, 26, false, false],

            // Monitor
            ['AOC 24G4E 24 Inch 180Hz IPS', 'Monitor', 'AOC', 2199000, 1999000, 13, true, true],
            ['AOC 27G4 27 Inch 180Hz IPS', 'Monitor', 'AOC', 2899000, null, 10, false, true],
            ['ASUS TUF Gaming VG249Q3A 180Hz', 'Monitor', 'ASUS', 2599000, 2399000, 9, true, false],
            ['MSI G244F E2 180Hz IPS', 'Monitor', 'MSI', 2299000, null, 12, false, false],

            // Keyboard
            ['Logitech G413 SE Mechanical Keyboard', 'Keyboard', 'Logitech', 899000, 799000, 19, true, false],
            ['Razer BlackWidow V4 X', 'Keyboard', 'Razer', 1999000, 1799000, 9, true, true],
            ['Fantech Maxfit61 Frost Wireless', 'Keyboard', 'Fantech', 699000, null, 25, false, true],
            ['Logitech MX Keys S Wireless', 'Keyboard', 'Logitech', 1599000, null, 12, false, false],

            // Mouse
            ['Logitech G102 Lightsync', 'Mouse', 'Logitech', 299000, 249000, 50, true, false],
            ['Logitech G304 Lightspeed Wireless', 'Mouse', 'Logitech', 599000, 549000, 33, true, true],
            ['Razer DeathAdder Essential', 'Mouse', 'Razer', 399000, null, 27, false, false],
            ['Razer Viper V3 HyperSpeed', 'Mouse', 'Razer', 1099000, 999000, 14, true, true],
            ['Fantech Helios UX3 V2', 'Mouse', 'Fantech', 499000, null, 20, false, false],

            // Headset & Speaker
            ['Logitech G335 Gaming Headset', 'Headset', 'Logitech', 799000, 699000, 18, true, false],
            ['Razer BlackShark V2 X', 'Headset', 'Razer', 999000, 899000, 16, true, true],
            ['Fantech HG11 Captain 7.1', 'Headset', 'Fantech', 349000, null, 24, false, false],
            ['Logitech Z120 Compact Speaker', 'Speaker', 'Logitech', 199000, null, 40, false, false],
            ['Razer Nommo V2 X Speaker', 'Speaker', 'Razer', 2499000, 2299000, 7, true, true],

            // Networking
            ['TP-Link Archer AX23 WiFi 6 Router', 'Router', 'TP-Link', 899000, 829000, 15, true, true],
            ['TP-Link Archer C64 AC1200 Router', 'Router', 'TP-Link', 499000, null, 22, false, false],
            ['TP-Link LAN Cable Cat6 10 Meter', 'LAN Cable', 'TP-Link', 79000, null, 100, false, false],
            ['TP-Link LAN Cable Cat6 20 Meter', 'LAN Cable', 'TP-Link', 129000, 109000, 80, false, true],
        ];

        foreach ($products as $index => $item) {
            [
                $name,
                $categoryName,
                $brandName,
                $price,
                $salePrice,
                $stock,
                $isFeatured,
                $isNew,
            ] = $item;

            Product::updateOrCreate(
                ['slug' => Str::slug($name)],
                [
                    'category_id' => $categoryModels[$categoryName]->id,
                    'brand_id' => $brands[$brandName]->id,
                    'sku' => strtoupper(Str::slug($brandName, '')) . '-' . str_pad($index + 1, 4, '0', STR_PAD_LEFT),
                    'name' => $name,
                    'description' => $this->makeDescription($name, $categoryName, $brandName),
                    'price' => $price,
                    'sale_price' => $salePrice,
                    'stock' => $stock,
                    'image' => null,
                    'is_featured' => $isFeatured,
                    'is_new' => $isNew,
                    'is_active' => true,
                    'sort_order' => $index + 1,
                ]
            );
        }
    }

    private function makeDescription(string $name, string $category, string $brand): string
    {
        return "{$name} adalah produk {$category} dari {$brand} yang cocok untuk kebutuhan build PC, upgrade komputer, gaming, editing, dan penggunaan harian. Produk ini dipilih untuk melengkapi katalog Compify dengan kualitas yang baik, harga kompetitif, serta dukungan stok yang bisa diatur melalui admin panel.

Spesifikasi dan detail produk dapat kamu ubah kembali dari halaman admin. Kamu juga bisa menambahkan gambar produk, harga promo, stok, status produk baru, dan status produk unggulan sesuai kebutuhan toko.";
    }
}
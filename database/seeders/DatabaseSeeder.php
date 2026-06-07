<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        /*
        |--------------------------------------------------------------------------
        | Admin Default
        |--------------------------------------------------------------------------
        */

        User::updateOrCreate(
            ['email' => 'admin@compify.test'],
            [
                'name' => 'Admin Compify',
                'password' => Hash::make('password'),
                'role' => 'admin',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Demo Data
        |--------------------------------------------------------------------------
        | Urutan penting:
        | 1. DemoProductSeeder membuat kategori, brand, produk, dan foto produk.
        | 2. BannerSeeder membuat hero slider.
        | 3. HomeSectionSeeder membuat section home setelah hero.
        */

        $this->call([
            DemoProductSeeder::class,
            BannerSeeder::class,
            HomeSectionSeeder::class,
            ShippingMethodSeeder::class,
            AboutSectionSeeder::class,
            BrandLogoSeeder::class,
        ]);
    }
}

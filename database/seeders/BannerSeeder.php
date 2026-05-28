<?php

namespace Database\Seeders;

use App\Models\Banner;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class BannerSeeder extends Seeder
{
    public function run(): void
    {
        $banners = [
            [
                'title' => 'Build PC Impianmu di Compify',
                'subtitle' => 'Temukan motherboard, PSU, RAM, SSD, casing, dan perlengkapan komputer terbaik untuk setup kamu.',
                'button_text' => 'Belanja Sekarang',
                'button_url' => '/products',
                'image_name' => 'hero-1',
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'title' => 'Upgrade Performa Komputermu',
                'subtitle' => 'Pilih komponen terbaik untuk gaming, editing, sekolah, dan produktivitas harian.',
                'button_text' => 'Lihat Produk',
                'button_url' => '/products',
                'image_name' => 'hero-2',
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'title' => 'Komponen PC Original & Siap Pakai',
                'subtitle' => 'Belanja peripheral, storage, monitor, dan komponen PC dengan tampilan katalog yang rapi.',
                'button_text' => 'Explore Now',
                'button_url' => '/products',
                'image_name' => 'hero-3',
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($banners as $banner) {
            $imagePath = $this->seedBannerImage($banner['image_name']);

            $payload = [
                'title' => $banner['title'],
                'subtitle' => $banner['subtitle'],
                'button_text' => $banner['button_text'],
                'button_url' => $banner['button_url'],
                'is_active' => $banner['is_active'],
                'sort_order' => $banner['sort_order'],
            ];

            /*
             * Kalau gambar ditemukan, kolom image akan diisi.
             * Kalau gambar tidak ditemukan, image lama tidak ditimpa jadi null.
             */
            if ($imagePath) {
                $payload['image'] = $imagePath;
            }

            Banner::updateOrCreate(
                ['sort_order' => $banner['sort_order']],
                $payload
            );
        }
    }

    private function seedBannerImage(string $fileNameWithoutExtension): ?string
    {
        $extensions = ['webp', 'jpg', 'jpeg', 'png'];

        foreach ($extensions as $extension) {
            $sourcePath = public_path("assets/seed/banners/{$fileNameWithoutExtension}.{$extension}");

            if (! File::exists($sourcePath)) {
                continue;
            }

            $targetRelativePath = "banners/{$fileNameWithoutExtension}.{$extension}";
            $targetFullPath = storage_path("app/public/{$targetRelativePath}");

            File::ensureDirectoryExists(dirname($targetFullPath));
            File::copy($sourcePath, $targetFullPath);

            return $targetRelativePath;
        }

        return null;
    }
}
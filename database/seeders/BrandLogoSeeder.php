<?php

namespace Database\Seeders;

use App\Models\Brand;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class BrandLogoSeeder extends Seeder
{
    /**
     * Logo sumber kamu ada di:
     * public/assets/seed/brands
     */
    private string $sourceDir = 'assets/seed/brands';

    /**
     * Logo akan dicopy ke:
     * storage/app/public/brands
     *
     * Database akan menyimpan:
     * brands/asus.png
     */
    private string $targetDir = 'brands';

    private array $extensions = [
        'webp',
        'png',
        'jpg',
        'jpeg',
        'svg',
    ];

    public function run(): void
    {
        $sourcePath = public_path($this->sourceDir);

        if (! File::isDirectory($sourcePath)) {
            $this->command?->warn("Folder logo brand tidak ditemukan: {$sourcePath}");
            return;
        }

        $brands = Brand::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        if ($brands->isEmpty()) {
            $this->command?->warn('Belum ada data brand. Jalankan DemoProductSeeder dulu.');
            return;
        }

        foreach ($brands as $brand) {
            $slug = $brand->slug ?: Str::slug($brand->name);

            $logoSource = $this->findLogoFile($slug);

            if (! $logoSource) {
                $this->command?->warn("Logo tidak ditemukan untuk brand: {$brand->name} ({$slug})");
                continue;
            }

            $extension = strtolower(pathinfo($logoSource, PATHINFO_EXTENSION));
            $targetPath = "{$this->targetDir}/{$slug}.{$extension}";

            Storage::disk('public')->put(
                $targetPath,
                File::get($logoSource)
            );

            $brand->update([
                'logo' => $targetPath,
            ]);

            $this->command?->info("Logo brand berhasil diupdate: {$brand->name}");
        }

        $this->command?->info('Brand logo seeder selesai.');
    }

    private function findLogoFile(string $slug): ?string
    {
        $basePath = public_path($this->sourceDir);

        foreach ($this->extensions as $extension) {
            $path = "{$basePath}/{$slug}.{$extension}";

            if (File::exists($path)) {
                return $path;
            }
        }

        return null;
    }
}
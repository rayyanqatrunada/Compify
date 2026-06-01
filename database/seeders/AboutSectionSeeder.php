<?php

namespace Database\Seeders;

use App\Models\AboutSection;
use Illuminate\Database\Seeder;

class AboutSectionSeeder extends Seeder
{
    public function run(): void
    {
        $sections = [
            [
                'section_type' => AboutSection::TYPE_HERO,
                'title' => 'About Us',
                'sort_order' => 1,
            ],
            [
                'section_type' => AboutSection::TYPE_INTRO,
                'description' => 'Compify adalah reseller resmi perlengkapan dan sparepart komputer terlengkap bergaransi resmi. Kami menghadirkan komponen orisinal berkualitas dengan proses belanja praktis dan pengiriman aman. Compify siap jadi partner andalan untuk penuhi segala kebutuhan rakitan dan digitalmu.',
                'button_text' => 'Belanja Sekarang',
                'button_url' => '/products',
                'sort_order' => 1,
            ],
            [
                'section_type' => AboutSection::TYPE_STATS,
                'title' => 'Produk',
                'stat_value' => '99+',
                'sort_order' => 1,
            ],
            [
                'section_type' => AboutSection::TYPE_STATS,
                'title' => 'Partner',
                'stat_value' => '99+',
                'sort_order' => 2,
            ],
            [
                'section_type' => AboutSection::TYPE_STATS,
                'title' => 'Pelanggan',
                'stat_value' => '99+',
                'sort_order' => 3,
            ],
            [
                'section_type' => AboutSection::TYPE_QUOTE,
                'description' => 'Compify dibangun di atas satu ide sederhana yaitu penyediaan komponen komputer harus cepat dan andal. Kami fokus menjaga kelancaran bisnis digital Anda.',
                'sort_order' => 1,
            ],
            [
                'section_type' => AboutSection::TYPE_VALUE,
                'title' => 'Keaslian',
                'description' => 'Semua suku cadang 100% orisinal dan dilindungi garansi resmi distributor.',
                'icon' => 'OK',
                'sort_order' => 1,
            ],
            [
                'section_type' => AboutSection::TYPE_VALUE,
                'title' => 'Ketersediaan',
                'description' => 'Stok komponen selalu siap untuk memastikan bisnis Anda tidak pernah tertunda.',
                'icon' => 'STK',
                'sort_order' => 2,
            ],
            [
                'section_type' => AboutSection::TYPE_VALUE,
                'title' => 'Keterjangkauan',
                'description' => 'Penawaran harga terbaik dan kompetitif khusus untuk kebutuhan reseller.',
                'icon' => 'IDR',
                'sort_order' => 3,
            ],
        ];

        foreach ($sections as $section) {
            AboutSection::updateOrCreate(
                [
                    'section_type' => $section['section_type'],
                    'sort_order' => $section['sort_order'],
                ],
                $section + ['is_active' => true]
            );
        }
    }
}

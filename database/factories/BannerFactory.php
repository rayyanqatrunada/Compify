<?php

namespace Database\Factories;

use App\Models\Banner;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Banner>
 */
class BannerFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'title' => fake()->randomElement(['Upgrade Battle Station', 'Desk Setup Essentials', 'Creator Kit Week']),
            'subtitle' => fake()->sentence(12),
            'badge' => fake()->randomElement(['Hot Deals', 'New Arrival', 'Limited Drop']),
            'image' => 'https://images.unsplash.com/photo-1618477388954-7852f32655ec?auto=format&fit=crop&w=1400&q=80',
            'cta_label' => 'Lihat koleksi',
            'cta_url' => '/products',
            'is_active' => true,
            'starts_at' => now()->subDays(3),
            'ends_at' => now()->addDays(21),
            'sort_order' => fake()->numberBetween(1, 5),
        ];
    }
}

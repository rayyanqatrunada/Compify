<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'AeroKey Pro 75',
            'NovaClick Wireless',
            'VisionCurve 27',
            'PulseWave Headset',
            'LiftDock Laptop Stand',
            'Orbit Deskmat XL',
            'StreamMic Mini',
            'FocusCam 2K',
            'CableHub Thunder',
            'CoolPad Alloy',
        ]);

        $price = fake()->numberBetween(249000, 6999000);

        return [
            'category_id' => Category::factory(),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'sku' => 'CMP-'.fake()->unique()->bothify('??###'),
            'short_description' => fake()->sentence(12),
            'description' => fake()->paragraphs(3, true),
            'price' => $price,
            'compare_price' => fake()->boolean(45) ? $price + fake()->numberBetween(100000, 850000) : null,
            'stock' => fake()->numberBetween(5, 120),
            'thumbnail' => 'https://images.unsplash.com/photo-1587829741301-dc798b83add3?auto=format&fit=crop&w=900&q=80',
            'gallery' => [
                'https://images.unsplash.com/photo-1618384887929-16ec33fab9ef?auto=format&fit=crop&w=900&q=80',
                'https://images.unsplash.com/photo-1542393545-10f5cde2c810?auto=format&fit=crop&w=900&q=80',
            ],
            'specs' => [
                'Material' => fake()->randomElement(['Aluminium', 'Polycarbonate', 'Carbon texture']),
                'Warranty' => fake()->randomElement(['1 tahun', '2 tahun']),
                'Connection' => fake()->randomElement(['USB-C', 'Bluetooth', '2.4GHz Wireless']),
            ],
            'is_featured' => fake()->boolean(35),
            'status' => 'active',
            'sold_count' => fake()->numberBetween(10, 650),
            'rating' => fake()->randomFloat(1, 4.4, 5.0),
        ];
    }
}

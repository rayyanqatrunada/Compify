<?php

namespace Database\Factories;

use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Mechanical Keyboard',
            'Gaming Mouse',
            'Monitor',
            'Headset',
            'Desk Accessories',
        ]);

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(10, 999),
            'description' => fake()->sentence(14),
            'image' => fake()->imageUrl(900, 600, 'technology', true),
            'accent_color' => fake()->randomElement(['#38bdf8', '#22d3ee', '#60a5fa', '#34d399', '#a78bfa']),
            'is_active' => true,
        ];
    }
}

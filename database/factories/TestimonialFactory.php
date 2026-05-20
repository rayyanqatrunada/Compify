<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->name();

        return [
            'name' => $name,
            'role' => fake()->randomElement(['UI Designer', 'Student PPLG', 'Streamer', 'Software Developer']),
            'company' => fake()->randomElement(['SMK Tech Lab', 'Studio Desk', 'Indie Workspace', 'CodeSpace']),
            'avatar' => 'https://ui-avatars.com/api/?background=0f172a&color=38bdf8&name='.urlencode($name),
            'quote' => fake()->sentence(18),
            'rating' => fake()->numberBetween(4, 5),
            'is_featured' => true,
        ];
    }
}

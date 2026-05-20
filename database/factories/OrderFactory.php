<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $subtotal = fake()->numberBetween(400000, 9000000);
        $shipping = fake()->numberBetween(15000, 75000);
        $discount = fake()->boolean(35) ? fake()->numberBetween(25000, 350000) : 0;

        return [
            'user_id' => User::factory(),
            'order_number' => 'CMP-'.now()->format('ym').'-'.fake()->unique()->numberBetween(10000, 99999),
            'status' => fake()->randomElement(['pending', 'processing', 'shipped', 'completed', 'cancelled']),
            'customer_name' => fake()->name(),
            'customer_email' => fake()->safeEmail(),
            'customer_phone' => fake()->phoneNumber(),
            'shipping_address' => fake()->address(),
            'subtotal' => $subtotal,
            'shipping_cost' => $shipping,
            'discount' => $discount,
            'total' => $subtotal + $shipping - $discount,
            'payment_method' => fake()->randomElement(['Bank Transfer', 'E-Wallet', 'Virtual Account']),
            'notes' => fake()->optional()->sentence(10),
            'ordered_at' => fake()->dateTimeBetween('-45 days', 'now'),
        ];
    }
}

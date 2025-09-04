<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Donation>
 */
class DonationFactory extends Factory
{
    protected $model = Donation::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'amount' => $this->faker->randomFloat(2, 5, 1000),
            'campaign_id' => Campaign::factory(),
            'user_id' => User::factory(),
            'payment_method' => $this->faker->randomElement(['credit_card', 'debit_card', 'paypal', 'bank_transfer', 'mock']),
            'transaction_id' => 'TXN_'.$this->faker->unique()->bothify('??########'),
            'status' => $this->faker->randomElement(['pending', 'completed', 'failed', 'refunded']),
            'anonymous' => $this->faker->boolean(20), // 20% chance of being anonymous
            'message' => $this->faker->optional(0.7)->sentence(10), // 70% chance of having a message
        ];
    }

    /**
     * Indicate that the donation is completed.
     */
    public function completed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
        ]);
    }

    /**
     * Indicate that the donation is pending.
     */
    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'transaction_id' => null,
        ]);
    }

    /**
     * Indicate that the donation is anonymous.
     */
    public function anonymous(): static
    {
        return $this->state(fn (array $attributes) => [
            'anonymous' => true,
        ]);
    }

    /**
     * Indicate that the donation has a specific amount.
     */
    public function amount(float $amount): static
    {
        return $this->state(fn (array $attributes) => [
            'amount' => $amount,
        ]);
    }

    /**
     * Indicate that the donation is failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'failed',
        ]);
    }

    /**
     * Indicate that the donation has no message.
     */
    public function withoutMessage(): static
    {
        return $this->state(fn (array $attributes) => [
            'message' => null,
        ]);
    }
}

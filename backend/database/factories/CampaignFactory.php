<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $targetAmount = $this->faker->randomFloat(2, 500, 50000);
        $currentAmount = $this->faker->randomFloat(2, 0, $targetAmount * 0.8);
        $startDate = $this->faker->dateTimeBetween('now', '+1 month');
        $endDate = $this->faker->dateTimeBetween($startDate, '+6 months');

        return [
            'title' => $this->faker->sentence(4),
            'description' => $this->faker->paragraphs(3, true),
            'target_amount' => $targetAmount,
            'current_amount' => $currentAmount,
            'start_date' => $startDate,
            'end_date' => $endDate,
            'status' => $this->faker->randomElement(['draft', 'active', 'completed', 'cancelled']),
            'category_id' => function (array $attributes) {
                // If category_id is provided in the factory call, use it
                // Otherwise, use an existing category or create a new one
                $category = CampaignCategory::inRandomOrder()->first();
                return $category ? $category->id : CampaignCategory::factory();
            },
            'user_id' => User::factory(),
            'featured' => $this->faker->boolean(20), // 20% chance of being featured
        ];
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'end_date' => $this->faker->dateTimeBetween('now', '+3 months'),
        ]);
    }

    /**
     * Indicate that the campaign is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'featured' => true,
        ]);
    }

    /**
     * Indicate that the campaign is ending soon.
     */
    public function endingSoon(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
            'start_date' => $this->faker->dateTimeBetween('-2 months', '-1 month'),
            'end_date' => $this->faker->dateTimeBetween('now', '+1 week'),
        ]);
    }

    /**
     * Indicate that the campaign is completed.
     */
    public function completed(): static
    {
        return $this->state(function (array $attributes) {
            $targetAmount = $attributes['target_amount'] ?? $this->faker->randomFloat(2, 500, 50000);
            return [
                'status' => 'completed',
                'current_amount' => $targetAmount,
                'end_date' => $this->faker->dateTimeBetween('-1 month', '-1 day'),
            ];
        });
    }
}

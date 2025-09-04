<?php

namespace Database\Factories;

use App\Models\CampaignCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\CampaignCategory>
 */
class CampaignCategoryFactory extends Factory
{
    protected $model = CampaignCategory::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        // Generate unique names using sequences to avoid slug conflicts
        $baseName = $this->faker->randomElement([
            'Healthcare',
            'Education', 
            'Environment',
            'Community Development',
            'Poverty Alleviation',
            'Animal Welfare',
            'Arts & Culture',
            'Technology',
            'Sports & Recreation',
            'Emergency Relief',
        ]);
        
        // Add sequence number to ensure uniqueness across test runs
        $sequenceNumber = $this->faker->unique()->numberBetween(1, 99999);
        $name = $baseName . ' ' . $sequenceNumber;
        
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => $this->faker->sentence(10),
            'icon' => $this->faker->randomElement([
                'heart', 'book', 'leaf', 'users', 'hand-holding-usd',
                'paw', 'palette', 'laptop', 'basketball', 'shield',
            ]),
        ];
    }
}

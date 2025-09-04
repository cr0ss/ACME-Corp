<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    public function run(): void
    {
        $categories = CampaignCategory::all();
        $users = User::all();

        $campaigns = [
            [
                'title' => 'Emergency Medical Fund for Local Family',
                'description' => 'Help support the Johnson family with unexpected medical expenses after their daughter\'s emergency surgery. Every contribution makes a difference in their time of need.',
                'target_amount' => 5000.00,
                'current_amount' => 2350.00,
                'start_date' => now()->subDays(10),
                'end_date' => now()->addDays(20),
                'status' => 'active',
                'featured' => true,
                'category_id' => $categories->where('slug', 'healthcare')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'School Supplies Drive for Underprivileged Students',
                'description' => 'Provide essential school supplies including notebooks, pencils, backpacks, and calculators for students from low-income families in our community.',
                'target_amount' => 3000.00,
                'current_amount' => 1850.00,
                'start_date' => now()->subDays(5),
                'end_date' => now()->addDays(25),
                'status' => 'active',
                'featured' => true,
                'category_id' => $categories->where('slug', 'education')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'Community Garden Project',
                'description' => 'Transform the vacant lot on Maple Street into a thriving community garden where neighbors can grow fresh produce and build stronger connections.',
                'target_amount' => 4500.00,
                'current_amount' => 3200.00,
                'start_date' => now()->subDays(15),
                'end_date' => now()->addDays(15),
                'status' => 'active',
                'featured' => false,
                'category_id' => $categories->where('slug', 'community-development')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'Tree Planting Initiative',
                'description' => 'Plant 100 native trees throughout our city to improve air quality, provide shade, and create a more beautiful environment for future generations.',
                'target_amount' => 2500.00,
                'current_amount' => 2500.00,
                'start_date' => now()->subDays(30),
                'end_date' => now()->subDays(1),
                'status' => 'completed',
                'featured' => false,
                'category_id' => $categories->where('slug', 'environmental')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'Hurricane Relief Fund',
                'description' => 'Provide immediate assistance to families affected by Hurricane Maria, including temporary housing, food supplies, and emergency medical care.',
                'target_amount' => 10000.00,
                'current_amount' => 7850.00,
                'start_date' => now()->subDays(7),
                'end_date' => now()->addDays(8),
                'status' => 'active',
                'featured' => true,
                'category_id' => $categories->where('slug', 'disaster-relief')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'Senior Center Technology Program',
                'description' => 'Help elderly residents learn to use smartphones and tablets to stay connected with family and access important services online.',
                'target_amount' => 1500.00,
                'current_amount' => 950.00,
                'start_date' => now()->subDays(3),
                'end_date' => now()->addDays(17),
                'status' => 'active',
                'featured' => false,
                'category_id' => $categories->where('slug', 'elderly-care')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'After-School Program for At-Risk Youth',
                'description' => 'Provide a safe space and educational support for children in low-income neighborhoods, including homework help, mentoring, and recreational activities.',
                'target_amount' => 6000.00,
                'current_amount' => 4200.00,
                'start_date' => now()->subDays(20),
                'end_date' => now()->addDays(10),
                'status' => 'active',
                'featured' => false,
                'category_id' => $categories->where('slug', 'child-welfare')->first()?->id,
                'user_id' => $users->random()->id,
            ],
            [
                'title' => 'Mobile Health Clinic for Rural Areas',
                'description' => 'Fund a mobile health clinic to provide basic medical care, screenings, and vaccinations to underserved rural communities in our region.',
                'target_amount' => 8000.00,
                'current_amount' => 1200.00,
                'start_date' => now()->addDays(1),
                'end_date' => now()->addDays(45),
                'status' => 'active',
                'featured' => false,
                'category_id' => $categories->where('slug', 'healthcare')->first()?->id,
                'user_id' => $users->random()->id,
            ],
        ];

        foreach ($campaigns as $campaignData) {
            Campaign::create($campaignData);
        }

        // Generate additional 152 campaigns using factory (to reach 160 total: 8 manual + 152 factory)
        Campaign::factory(152)->create();
    }
}

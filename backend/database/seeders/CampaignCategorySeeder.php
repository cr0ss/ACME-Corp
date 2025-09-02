<?php

namespace Database\Seeders;

use App\Models\CampaignCategory;
use Illuminate\Database\Seeder;

class CampaignCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Healthcare',
                'slug' => 'healthcare',
                'description' => 'Medical treatments, hospital bills, and health-related expenses',
                'icon' => '🏥',
            ],
            [
                'name' => 'Education',
                'slug' => 'education',
                'description' => 'School fees, educational materials, and learning support',
                'icon' => '📚',
            ],
            [
                'name' => 'Community Development',
                'slug' => 'community-development',
                'description' => 'Infrastructure, community centers, and local improvements',
                'icon' => '🏘️',
            ],
            [
                'name' => 'Environmental',
                'slug' => 'environmental',
                'description' => 'Tree planting, clean-up drives, and environmental initiatives',
                'icon' => '🌱',
            ],
            [
                'name' => 'Disaster Relief',
                'slug' => 'disaster-relief',
                'description' => 'Emergency assistance and disaster recovery support',
                'icon' => '🆘',
            ],
            [
                'name' => 'Elderly Care',
                'slug' => 'elderly-care',
                'description' => 'Support for senior citizens and elderly care facilities',
                'icon' => '👴',
            ],
            [
                'name' => 'Child Welfare',
                'slug' => 'child-welfare',
                'description' => 'Supporting children in need and child welfare programs',
                'icon' => '👶',
            ],
        ];

        foreach ($categories as $category) {
            CampaignCategory::create($category);
        }
    }
}
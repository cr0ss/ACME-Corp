<?php

namespace Tests;

use Database\Seeders\CampaignCategorySeeder;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

abstract class TestCase extends BaseTestCase
{
    /**
     * Safely seed campaign categories if needed.
     * This handles cases where tables might not exist due to flaky RefreshDatabase.
     */
    protected function seedCampaignCategoriesIfNeeded(): void
    {
        try {
            // Check if table exists and if we need to seed
            if (Schema::hasTable('campaign_categories')) {
                $count = DB::table('campaign_categories')->count();
                if ($count === 0) {
                    $this->seed(CampaignCategorySeeder::class);
                }
            } else {
                // Table doesn't exist, let RefreshDatabase handle it
                $this->forceRefreshDatabase();
                $this->seed(CampaignCategorySeeder::class);
            }
        } catch (\Exception $e) {
            // If anything goes wrong, force a complete refresh
            $this->forceRefreshDatabase();
            $this->seed(CampaignCategorySeeder::class);
        }
    }

    /**
     * Force a complete database refresh when things go wrong.
     */
    protected function forceRefreshDatabase(): void
    {
        $this->artisan('migrate:fresh');
    }
}

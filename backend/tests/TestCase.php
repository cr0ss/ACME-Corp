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
     * This will only affect the test database due to APP_ENV=testing.
     */
    protected function forceRefreshDatabase(): void
    {
        // Double-check we're in testing environment
        if (app()->environment('testing')) {
            $this->artisan('migrate:fresh');
        } else {
            throw new \Exception('Cannot refresh database outside of testing environment');
        }
    }

    /**
     * Ensure we're using the test database before running any tests
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Verify we're using the test database
        $currentDb = config('database.connections.pgsql_testing.database');
        if ($currentDb !== 'acme_csr_test') {
            throw new \Exception("Tests must run against test database 'acme_csr_test', not '{$currentDb}'");
        }
    }
}

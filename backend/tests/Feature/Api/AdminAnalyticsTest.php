<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use robust seeding method that handles database corruption
        $this->seedCampaignCategoriesIfNeeded();
    }

    public function test_admin_can_access_dashboard(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();

        // Create some test data
        $users = User::factory()->count(5)->create();
        $campaigns = Campaign::factory()->count(3)->create([
            'category_id' => $category->id,
            'user_id' => $users->random()->id,
        ]);
        Donation::factory()->count(10)->create([
            'user_id' => $users->random()->id,
            'campaign_id' => $campaigns->random()->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertOk()
            ->assertJsonStructure([
                'overview' => [
                    'total_users',
                    'active_users',
                    'total_campaigns',
                    'active_campaigns',
                    'total_donations',
                    'total_raised',
                    'avg_donation',
                    'success_rate',
                ],
                'recent_activity' => [
                    'recent_donations',
                    'recent_campaigns',
                    'recent_users',
                ],
                'trends' => [
                    'monthly_donations',
                    'top_categories',
                    'top_donors',
                    'campaign_performance',
                ],
            ]);

        // Verify some data
        $data = $response->json();
        $this->assertGreaterThanOrEqual(5, $data['overview']['total_users']);
        $this->assertGreaterThanOrEqual(3, $data['overview']['total_campaigns']);
        $this->assertGreaterThanOrEqual(10, $data['overview']['total_donations']);
    }

    public function test_non_admin_cannot_access_dashboard(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_can_get_donation_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        // Create donations over different periods
        Donation::factory()->count(5)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/donations?period=30&group_by=day');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_donations',
                    'total_amount',
                    'avg_donation',
                    'unique_donors',
                ],
                'trends',
                'by_payment_method',
                'by_department',
                'hourly_distribution',
            ]);
    }

    public function test_admin_can_get_campaign_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create();

        Campaign::factory()->count(3)->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'created_at' => now()->subDays(10),
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/campaigns?period=30');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_campaigns',
                    'success_rate',
                    'avg_target',
                    'avg_raised',
                ],
                'performance' => [
                    'top_campaigns',
                    'by_category',
                    'completion_rates',
                ],
                'engagement' => [
                    'most_donations',
                ],
            ]);
    }

    public function test_admin_can_get_user_analytics(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();

        // Create users with donations
        $users = User::factory()->count(5)->create();
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $users->first()->id,
        ]);

        foreach ($users->take(3) as $user) {
            Donation::factory()->count(2)->create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'status' => 'completed',
                'created_at' => now()->subDays(5),
            ]);
        }

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/users?period=30');

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_users',
                    'active_users',
                    'new_users',
                    'engagement_rate',
                ],
                'participation' => [
                    'by_department',
                    'top_donors',
                ],
                'growth',
            ]);

        // Verify engagement calculations
        $data = $response->json();
        $this->assertGreaterThanOrEqual(5, $data['summary']['total_users']);
        $this->assertGreaterThanOrEqual(3, $data['summary']['active_users']);
    }

    public function test_analytics_endpoints_require_admin_access(): void
    {
        $user = User::factory()->create(['is_admin' => false]);

        $endpoints = [
            '/api/admin/analytics/donations',
            '/api/admin/analytics/campaigns',
            '/api/admin/analytics/users',
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($user, 'sanctum')
                ->getJson($endpoint);

            $response->assertForbidden();
        }
    }

    public function test_analytics_with_date_filters(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        // Create donations at different times
        Donation::factory()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'created_at' => now()->subDays(10),
        ]);

        Donation::factory()->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'created_at' => now()->subDays(40), // Outside 30-day window
        ]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/donations?period=30');

        $response->assertOk();

        $data = $response->json();
        // Should only include donation from 10 days ago, not 40 days ago
        $this->assertEquals(1, $data['summary']['total_donations']);
    }

    public function test_analytics_with_different_grouping(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/donations?period=90&group_by=week');

        $response->assertOk()
            ->assertJsonStructure([
                'summary',
                'trends',
                'by_payment_method',
                'by_department',
                'hourly_distribution',
            ]);

        // Test month grouping
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/analytics/donations?period=365&group_by=month');

        $response->assertOk();
    }
}

<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminReportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use robust seeding method that handles database corruption
        $this->seedCampaignCategoriesIfNeeded();
    }

    public function test_admin_can_generate_financial_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create(['department' => 'Engineering']);
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        // Create donations for the report
        Donation::factory()->count(5)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'amount' => 100.00,
            'created_at' => now()->subDays(10),
        ]);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/reports/financial?start_date={$startDate}&end_date={$endDate}&group_by=month");

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_raised',
                    'total_donations',
                    'avg_donation',
                    'unique_donors',
                    'campaigns_funded',
                ],
                'trends',
                'by_category',
                'by_department',
                'by_payment_method',
                'top_campaigns',
                'top_donors',
            ]);

        $data = $response->json();
        $this->assertEquals(500.00, $data['summary']['total_raised']); // 5 × $100
        $this->assertEquals(5, $data['summary']['total_donations']);
        $this->assertEquals(100.00, $data['summary']['avg_donation']);
    }

    public function test_admin_can_generate_campaign_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create();

        // Create campaigns with different statuses
        $activeCampaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'active',
            'target_amount' => 1000.00,
            'current_amount' => 750.00,
            'created_at' => now()->subDays(15),
        ]);

        $completedCampaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'target_amount' => 500.00,
            'current_amount' => 600.00, // Over target
            'created_at' => now()->subDays(20),
        ]);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/reports/campaigns?start_date={$startDate}&end_date={$endDate}");

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_campaigns',
                    'total_target',
                    'total_raised',
                    'avg_target',
                    'avg_raised',
                    'success_rate',
                ],
                'by_status',
                'by_category',
                'performance_ranges',
                'detailed_campaigns',
            ]);

        $data = $response->json();
        $this->assertEquals(2, $data['summary']['total_campaigns']);
        $this->assertEquals(1500.00, $data['summary']['total_target']); // 1000 + 500
        $this->assertEquals(1350.00, $data['summary']['total_raised']); // 750 + 600
    }

    public function test_admin_can_generate_user_engagement_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();

        // Create users in different departments
        $engineeringUsers = User::factory()->count(3)->create(['department' => 'Engineering']);
        $marketingUsers = User::factory()->count(2)->create(['department' => 'Marketing']);
        
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $engineeringUsers->first()->id,
        ]);

        // Create donations for some users
        foreach ($engineeringUsers->take(2) as $user) {
            Donation::factory()->count(2)->create([
                'user_id' => $user->id,
                'campaign_id' => $campaign->id,
                'status' => 'completed',
                'created_at' => now()->subDays(10),
            ]);
        }

        // One marketing user makes a donation
        Donation::factory()->create([
            'user_id' => $marketingUsers->first()->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'created_at' => now()->subDays(5),
        ]);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/reports/user-engagement?start_date={$startDate}&end_date={$endDate}");

        $response->assertOk()
            ->assertJsonStructure([
                'summary' => [
                    'total_users',
                    'active_users',
                    'engagement_rate',
                    'avg_donations_per_user',
                    'avg_amount_per_user',
                ],
                'by_department',
                'participation_levels',
                'top_participants',
            ]);

        $data = $response->json();
        $this->assertGreaterThanOrEqual(6, $data['summary']['total_users']); // 5 created + 1 admin
        $this->assertEquals(3, $data['summary']['active_users']); // 2 eng + 1 marketing
    }

    public function test_admin_can_generate_impact_report(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create(['department' => 'Engineering']);

        // Create successful campaign
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
            'status' => 'completed',
            'target_amount' => 1000.00,
            'current_amount' => 1200.00,
            'end_date' => now()->subDays(5),
        ]);

        // Create donations
        Donation::factory()->count(10)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
            'amount' => 120.00,
            'created_at' => now()->subDays(10),
        ]);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/reports/impact?start_date={$startDate}&end_date={$endDate}");

        $response->assertOk()
            ->assertJsonStructure([
                'overview' => [
                    'total_funds_raised',
                    'campaigns_completed',
                    'employees_participated',
                    'beneficiary_categories',
                ],
                'category_impact',
                'monthly_impact',
                'success_stories',
                'department_participation',
            ]);

        $data = $response->json();
        $this->assertEquals(1200.00, $data['overview']['total_funds_raised']);
        $this->assertEquals(1, $data['overview']['campaigns_completed']);
    }

    public function test_admin_can_export_different_data_types(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category = CampaignCategory::first();
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);

        Donation::factory()->count(3)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
            'status' => 'completed',
        ]);

        $exportTypes = ['donations', 'campaigns', 'users', 'financial', 'impact'];

        foreach ($exportTypes as $type) {
            $response = $this->actingAs($admin, 'sanctum')
                ->postJson('/api/admin/export', [
                    'type' => $type,
                    'format' => 'csv',
                    'start_date' => now()->subDays(30)->format('Y-m-d'),
                    'end_date' => now()->format('Y-m-d'),
                ]);

            $response->assertOk()
                ->assertJsonStructure([
                    'data',
                    'type',
                    'format',
                    'filename',
                    'count',
                    'generated_at',
                ]);

            $this->assertEquals($type, $response->json('type'));
            $this->assertEquals('csv', $response->json('format'));
        }
    }

    public function test_reports_require_valid_date_range(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Test missing dates
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/reports/financial');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['start_date', 'end_date']);

        // Test invalid date range (end before start)
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/admin/reports/financial?start_date=2024-12-01&end_date=2024-11-01');

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['end_date']);
    }

    public function test_reports_can_filter_by_category(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $category1 = CampaignCategory::first();
        $category2 = CampaignCategory::skip(1)->first();
        $user = User::factory()->create();

        // Create campaigns in different categories
        $campaign1 = Campaign::factory()->create([
            'category_id' => $category1->id,
            'user_id' => $user->id,
        ]);
        $campaign2 = Campaign::factory()->create([
            'category_id' => $category2->id,
            'user_id' => $user->id,
        ]);

        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        // Test filtering by specific category
        $response = $this->actingAs($admin, 'sanctum')
            ->getJson("/api/admin/reports/campaigns?start_date={$startDate}&end_date={$endDate}&category_id={$category1->id}");

        $response->assertOk();
        
        $campaigns = $response->json('detailed_campaigns');
        foreach ($campaigns as $campaign) {
            $this->assertEquals($category1->name, $campaign['category']);
        }
    }

    public function test_non_admin_cannot_access_reports(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $startDate = now()->subDays(30)->format('Y-m-d');
        $endDate = now()->format('Y-m-d');

        $endpoints = [
            "/api/admin/reports/financial?start_date={$startDate}&end_date={$endDate}",
            "/api/admin/reports/campaigns?start_date={$startDate}&end_date={$endDate}",
            "/api/admin/reports/user-engagement?start_date={$startDate}&end_date={$endDate}",
            "/api/admin/reports/impact?start_date={$startDate}&end_date={$endDate}",
        ];

        foreach ($endpoints as $endpoint) {
            $response = $this->actingAs($user, 'sanctum')
                ->getJson($endpoint);

            $response->assertForbidden();
        }

        // Test export endpoint
        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/admin/export', [
                'type' => 'donations',
                'format' => 'csv',
            ]);

        $response->assertForbidden();
    }

    public function test_export_validation(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);

        // Test missing required fields
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/export', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type', 'format']);

        // Test invalid type
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/export', [
                'type' => 'invalid_type',
                'format' => 'csv',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['type']);

        // Test invalid format
        $response = $this->actingAs($admin, 'sanctum')
            ->postJson('/api/admin/export', [
                'type' => 'donations',
                'format' => 'invalid_format',
            ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['format']);
    }
}

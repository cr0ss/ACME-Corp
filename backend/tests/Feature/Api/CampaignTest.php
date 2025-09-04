<?php

namespace Tests\Feature\Api;

use Tests\TestCase;
use App\Models\User;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Use robust seeding method that handles database corruption
        $this->seedCampaignCategoriesIfNeeded();
    }

    public function test_can_get_campaigns_list(): void
    {
        $category = CampaignCategory::first();
        Campaign::factory()->count(5)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/campaigns');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'target_amount',
                        'current_amount',
                        'start_date',
                        'end_date',
                        'status',
                        'featured',
                        'category' => [
                            'id',
                            'name',
                            'slug',
                            'description',
                            'icon',
                        ],
                        'user' => [
                            'id',
                            'name',
                            'employee_id',
                            'department',
                        ],
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

        $this->assertGreaterThan(0, count($response->json('data')));
    }

    public function test_can_get_campaign_by_id(): void
    {
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/campaigns/{$campaign->id}");

        $response->assertOk()
            ->assertJson([
                'campaign' => [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'description' => $campaign->description,
                    'target_amount' => $campaign->target_amount,
                    'current_amount' => $campaign->current_amount,
                    'status' => $campaign->status,
                ],
                'stats' => [
                    'total_donations' => 0,
                    'total_donated' => 0,
                ],
            ]);
    }

    public function test_returns_404_for_non_existent_campaign(): void
    {
        $response = $this->getJson('/api/campaigns/999');

        $response->assertNotFound();
    }

    public function test_can_get_trending_campaigns(): void
    {
        $category = CampaignCategory::first();
        Campaign::factory()->count(3)->featured()->create(['category_id' => $category->id]);
        Campaign::factory()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/campaigns/trending');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'featured',
                    ],
                ],
            ]);
    }

    public function test_can_get_ending_soon_campaigns(): void
    {
        $category = CampaignCategory::first();
        Campaign::factory()->count(3)->endingSoon()->create(['category_id' => $category->id]);
        Campaign::factory()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/campaigns/ending-soon');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'title',
                        'end_date',
                    ],
                ],
            ]);
    }

    public function test_authenticated_user_can_create_campaign(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::first();

        $campaignData = [
            'title' => 'Test Campaign',
            'description' => 'This is a test campaign description.',
            'target_amount' => 1000.00,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'category_id' => $category->id,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/campaigns', $campaignData);

        $response->assertCreated()
            ->assertJsonFragment([
                'title' => 'Test Campaign',
                'description' => 'This is a test campaign description.',
                'target_amount' => '1000.00',
                'status' => 'draft',
            ]);

        $this->assertDatabaseHas('campaigns', [
            'title' => 'Test Campaign',
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);
    }

    public function test_unauthenticated_user_cannot_create_campaign(): void
    {
        $category = CampaignCategory::first();

        $campaignData = [
            'title' => 'Test Campaign',
            'description' => 'This is a test campaign description.',
            'target_amount' => 1000.00,
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date' => now()->addMonth()->format('Y-m-d'),
            'category_id' => $category->id,
        ];

        $response = $this->postJson('/api/campaigns', $campaignData);

        $response->assertUnauthorized();
    }

    public function test_campaign_creation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/campaigns', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'title',
                'description',
                'target_amount',
                'start_date',
                'end_date',
                'category_id',
            ]);
    }

    public function test_user_can_update_own_campaign(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => 'Updated Campaign Title',
            'description' => $campaign->description,
            'target_amount' => $campaign->target_amount,
            'start_date' => $campaign->start_date->format('Y-m-d'),
            'end_date' => $campaign->end_date->format('Y-m-d'),
            'category_id' => $campaign->category_id,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/campaigns/{$campaign->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'title' => 'Updated Campaign Title',
            ]);

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
            'title' => 'Updated Campaign Title',
        ]);
    }

    public function test_user_cannot_update_others_campaign(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => 'Updated Campaign Title',
            'description' => $campaign->description,
            'target_amount' => $campaign->target_amount,
            'start_date' => $campaign->start_date->format('Y-m-d'),
            'end_date' => $campaign->end_date->format('Y-m-d'),
            'category_id' => $campaign->category_id,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->putJson("/api/campaigns/{$campaign->id}", $updateData);

        $response->assertForbidden();
    }

    public function test_user_can_delete_own_campaign(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertNoContent();

        $this->assertDatabaseMissing('campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_user_cannot_delete_others_campaign(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->deleteJson("/api/campaigns/{$campaign->id}");

        $response->assertForbidden();

        $this->assertDatabaseHas('campaigns', [
            'id' => $campaign->id,
        ]);
    }

    public function test_admin_can_update_any_campaign(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
        ]);

        $updateData = [
            'title' => 'Admin Updated Title',
            'description' => $campaign->description,
            'target_amount' => $campaign->target_amount,
            'start_date' => $campaign->start_date->format('Y-m-d'),
            'end_date' => $campaign->end_date->format('Y-m-d'),
            'category_id' => $campaign->category_id,
        ];

        $response = $this->actingAs($admin, 'sanctum')
            ->putJson("/api/campaigns/{$campaign->id}", $updateData);

        $response->assertOk()
            ->assertJsonFragment([
                'title' => 'Admin Updated Title',
            ]);
    }

    public function test_can_filter_campaigns_by_category(): void
    {
        // Create unique categories for this test to avoid interference
        $category = CampaignCategory::factory()->create([
            'name' => 'Test Category Filter ' . time(),
            'slug' => 'test-category-filter-' . time(),
        ]);
        $otherCategory = CampaignCategory::factory()->create([
            'name' => 'Other Test Category ' . time(),
            'slug' => 'other-test-category-' . time(),
        ]);

        $user = User::factory()->create();

        // Create campaigns with specific categories (active status to avoid draft filtering)
        Campaign::factory()->active()->count(3)->create([
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        Campaign::factory()->active()->count(2)->create([
            'category_id' => $otherCategory->id,
            'user_id' => $user->id,
        ]);

        $response = $this->getJson("/api/campaigns?category_id={$category->id}");

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));

        // Verify all returned campaigns belong to the correct category
        foreach ($response->json('data') as $campaign) {
            $this->assertEquals($category->id, $campaign['category']['id']);
        }
    }

    public function test_can_filter_campaigns_by_status(): void
    {
        Campaign::factory()->count(3)->create(['status' => 'active']);
        Campaign::factory()->count(2)->create(['status' => 'draft']);

        $response = $this->getJson('/api/campaigns?status=active');

        $response->assertOk();
        $this->assertCount(3, $response->json('data'));

        foreach ($response->json('data') as $campaign) {
            $this->assertEquals('active', $campaign['status']);
        }
    }
}

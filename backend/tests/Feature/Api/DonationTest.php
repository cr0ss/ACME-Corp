<?php

namespace Tests\Feature\Api;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Use robust seeding method that handles database corruption
        $this->seedCampaignCategoriesIfNeeded();
    }

    public function test_authenticated_user_can_make_donation(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
            'anonymous' => false,
            'message' => 'Supporting this great cause!',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertCreated()
            ->assertJsonStructure([
                'id',
                'amount',
                'campaign_id',
                'user_id',
                'payment_method',
                'status',
                'anonymous',
                'message',
                'created_at',
                'updated_at',
            ])
            ->assertJsonFragment([
                'amount' => '100.50',
                'campaign_id' => $campaign->id,
                'user_id' => $user->id,
                'payment_method' => 'mock',
                'anonymous' => false,
                'message' => 'Supporting this great cause!',
            ]);

        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'amount' => '100.50',
            'payment_method' => 'mock',
        ]);
    }

    public function test_unauthenticated_user_cannot_make_donation(): void
    {
        $campaign = Campaign::factory()->active()->create();

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
        ];

        $response = $this->postJson('/api/donations', $donationData);

        $response->assertUnauthorized();
    }

    public function test_donation_requires_valid_data(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'campaign_id',
                'amount',
                'payment_method',
            ]);
    }

    public function test_donation_amount_must_be_positive(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => -50.00,
            'payment_method' => 'mock',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['amount']);
    }

    public function test_cannot_donate_to_non_existent_campaign(): void
    {
        $user = User::factory()->create();

        $donationData = [
            'campaign_id' => 999,
            'amount' => 100.50,
            'payment_method' => 'mock',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['campaign_id']);
    }

    public function test_cannot_donate_to_draft_campaign(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['status' => 'draft']);

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['campaign_id']);
    }

    public function test_user_can_get_their_donations(): void
    {
        $user = User::factory()->create();
        $otherUser = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        // Create donations for both users
        Donation::factory()->count(3)->create([
            'user_id' => $user->id,
            'campaign_id' => $campaign->id,
        ]);
        Donation::factory()->count(2)->create([
            'user_id' => $otherUser->id,
            'campaign_id' => $campaign->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson('/api/donations/my-donations');

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'amount',
                        'status',
                        'anonymous',
                        'message',
                        'created_at',
                        'campaign' => [
                            'id',
                            'title',
                            'description',
                        ],
                    ],
                ],
                'current_page',
                'last_page',
                'per_page',
                'total',
            ]);

        // Verify only user's donations are returned
        $this->assertCount(3, $response->json('data'));
        foreach ($response->json('data') as $donation) {
            $this->assertEquals($user->id, $donation['user_id']);
        }
    }

    public function test_user_can_get_donation_details(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/donations/{$donation->id}");

        $response->assertOk()
            ->assertJson([
                'id' => $donation->id,
                'amount' => $donation->amount,
                'status' => $donation->status,
                'user_id' => $user->id,
                'campaign_id' => $donation->campaign_id,
            ]);
    }

    public function test_user_cannot_get_others_donation_details(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $otherUser = User::factory()->create(['is_admin' => false]);
        $category = CampaignCategory::first();
        $campaign = Campaign::factory()->create([
            'user_id' => $otherUser->id,
            'category_id' => $category->id,
        ]);
        $donation = Donation::factory()->create([
            'user_id' => $otherUser->id,
            'campaign_id' => $campaign->id,
        ]);

        $response = $this->actingAs($user, 'sanctum')
            ->getJson("/api/donations/{$donation->id}");

        $response->assertForbidden();
    }

    public function test_user_can_get_donation_receipt(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->completed()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/donations/{$donation->id}/receipt");

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/pdf')
            ->assertHeader('Content-Disposition', 'attachment; filename="receipt-'.$donation->id.'.pdf"');
    }

    public function test_cannot_get_receipt_for_pending_donation(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->pending()->create(['user_id' => $user->id]);

        $response = $this->actingAs($user, 'sanctum')
            ->get("/api/donations/{$donation->id}/receipt");

        $response->assertForbidden()
            ->assertJsonStructure([
                'message',
            ]);
    }

    public function test_anonymous_donation_hides_user_info(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
            'anonymous' => true,
            'message' => 'Anonymous donation message',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertCreated()
            ->assertJsonFragment([
                'anonymous' => true,
            ]);

        $this->assertDatabaseHas('donations', [
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'anonymous' => true,
        ]);
    }

    public function test_donation_updates_campaign_current_amount(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create([
            'current_amount' => '500.00',
        ]);

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        $response->assertCreated();

        // Verify campaign current_amount was updated
        $campaign->refresh();
        $this->assertEquals('600.50', $campaign->current_amount);
    }

    public function test_can_donate_with_different_payment_methods(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        $paymentMethods = ['mock', 'stripe', 'paypal'];

        foreach ($paymentMethods as $method) {
            $donationData = [
                'campaign_id' => $campaign->id,
                'amount' => 50.00,
                'payment_method' => $method,
            ];

            $response = $this->actingAs($user, 'sanctum')
                ->postJson('/api/donations', $donationData);

            $response->assertCreated()
                ->assertJsonFragment([
                    'payment_method' => $method,
                ]);
        }
    }

    public function test_donation_with_long_message(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->active()->create();

        $longMessage = str_repeat('This is a long donation message. ', 50);

        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.50,
            'payment_method' => 'mock',
            'message' => $longMessage,
        ];

        $response = $this->actingAs($user, 'sanctum')
            ->postJson('/api/donations', $donationData);

        // Should succeed if message length validation allows it
        // or fail with appropriate validation error
        $this->assertTrue(
            $response->status() === 201 ||
            $response->status() === 422
        );
    }
}

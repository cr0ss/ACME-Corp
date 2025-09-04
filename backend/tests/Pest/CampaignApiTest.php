<?php

use App\Models\Campaign;
use App\Models\User;
use App\Models\CampaignCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCampaignCategoriesIfNeeded();
    $this->user = User::factory()->create();
    $this->category = CampaignCategory::first();
});

test('user can view campaigns list', function () {
    Campaign::factory()->count(3)->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->user)
        ->getJson('/api/campaigns');

    $response->assertOk();
    
    $responseData = $response->json();
    expect($responseData)->toBeArray();
    expect(count($responseData))->toBeGreaterThan(0);
});

test('user can create a campaign', function () {
    $campaignData = [
        'title' => 'Save the Environment',
        'description' => 'Help us protect our planet',
        'target_amount' => 5000.00,
        'category_id' => $this->category->id,
        'start_date' => now()->format('Y-m-d'),
        'end_date' => now()->addDays(30)->format('Y-m-d'),
    ];

    $response = $this->actingAs($this->user)
        ->postJson('/api/campaigns', $campaignData);

    $response->assertCreated()
        ->assertJson([
            'campaign' => [
                'title' => 'Save the Environment',
                'description' => 'Help us protect our planet',
                'target_amount' => '5000.00',
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'title' => 'Save the Environment',
        'user_id' => $this->user->id,
    ]);
});

test('campaign creation requires authentication', function () {
    $campaignData = [
        'title' => 'Save the Environment',
        'description' => 'Help us protect our planet',
        'target_amount' => 5000.00,
    ];

    $response = $this->postJson('/api/campaigns', $campaignData);

    $response->assertUnauthorized();
});

test('campaign creation validates required fields', function () {
    $response = $this->actingAs($this->user)
        ->postJson('/api/campaigns', []);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['title', 'description', 'target_amount', 'category_id']);
});

test('user can view a specific campaign', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->getJson("/api/campaigns/{$campaign->id}");

    $response->assertOk()
        ->assertJson([
            'campaign' => [
                'id' => $campaign->id,
                'title' => $campaign->title,
            ]
        ]);
});

test('user can update their own campaign', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $updateData = [
        'title' => 'Updated Campaign Title',
        'description' => 'Updated description',
    ];

    $response = $this->actingAs($this->user)
        ->putJson("/api/campaigns/{$campaign->id}", $updateData);

    $response->assertOk()
        ->assertJson([
            'campaign' => [
                'title' => 'Updated Campaign Title',
                'description' => 'Updated description',
            ]
        ]);

    $this->assertDatabaseHas('campaigns', [
        'id' => $campaign->id,
        'title' => 'Updated Campaign Title',
    ]);
});

test('user cannot update another users campaign', function () {
    $otherUser = User::factory()->create();
    $campaign = Campaign::factory()->create([
        'user_id' => $otherUser->id,
        'category_id' => $this->category->id,
    ]);

    $updateData = ['title' => 'Hacked Campaign'];

    $response = $this->actingAs($this->user)
        ->putJson("/api/campaigns/{$campaign->id}", $updateData);

    $response->assertForbidden();
});

test('campaign can be deleted by owner', function () {
    $campaign = Campaign::factory()->create([
        'user_id' => $this->user->id,
        'category_id' => $this->category->id,
    ]);

    $response = $this->actingAs($this->user)
        ->deleteJson("/api/campaigns/{$campaign->id}");

    $response->assertNoContent();
    $this->assertDatabaseMissing('campaigns', ['id' => $campaign->id]);
});

<?php

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(Tests\TestCase::class, RefreshDatabase::class);

beforeEach(function () {
    $this->seedCampaignCategoriesIfNeeded();
});

test('campaign can be created with required fields', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'title' => 'Save the Trees',
        'description' => 'Help us plant more trees',
        'target_amount' => 10000.00,
        'status' => 'active',
    ]);

    expect($campaign->title)->toBe('Save the Trees');
    expect($campaign->user_id)->toBe($user->id);
    expect($campaign->category_id)->toBe($category->id);
    expect($campaign->target_amount)->toBe('10000.00');
});

test('campaign progress can be calculated', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'target_amount' => 1000.00,
        'current_amount' => 250.00,
    ]);

    $progress = ($campaign->current_amount / $campaign->target_amount) * 100;

    expect($progress)->toBe(25.0);
});

test('campaign can have different statuses', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $activeCampaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => 'active',
        'start_date' => now()->subDays(1),
        'end_date' => now()->addDays(30),
    ]);

    $draftCampaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'status' => 'draft',
        'start_date' => now()->subDays(10),
        'end_date' => now()->subDays(1),
    ]);

    expect($activeCampaign->status)->toBe('active');
    expect($draftCampaign->status)->toBe('draft');
});

test('campaign belongs to a user', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($campaign->user)->toBeInstanceOf(User::class);
    expect($campaign->user->id)->toBe($user->id);
});

test('campaign belongs to a category', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($campaign->category)->toBeInstanceOf(CampaignCategory::class);
    expect($campaign->category->id)->toBe($category->id);
});

test('campaign has all required fields', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
    ]);

    expect($campaign)->toHaveKeys([
        'id', 'user_id', 'category_id', 'title', 'description',
        'target_amount', 'current_amount', 'status', 'start_date', 'end_date',
    ]);
});

test('campaign can be featured', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $featuredCampaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'featured' => true,
    ]);

    $regularCampaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'featured' => false,
    ]);

    expect($featuredCampaign->featured)->toBeTrue();
    expect($regularCampaign->featured)->toBeFalse();
});

test('campaign dates are properly cast', function () {
    $user = User::factory()->create();
    $category = CampaignCategory::first();

    $startDate = now()->subDays(1);
    $endDate = now()->addDays(30);

    $campaign = Campaign::factory()->create([
        'user_id' => $user->id,
        'category_id' => $category->id,
        'start_date' => $startDate,
        'end_date' => $endDate,
    ]);

    expect($campaign->start_date)->toBeInstanceOf(\Carbon\Carbon::class);
    expect($campaign->end_date)->toBeInstanceOf(\Carbon\Carbon::class);
});

<?php

namespace Tests\Unit\Models;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_campaign_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::factory()->create();

        $campaignData = [
            'title' => 'Test Campaign',
            'description' => 'This is a test campaign description.',
            'target_amount' => 1000.00,
            'start_date' => '2025-01-01',
            'end_date' => '2025-02-01',
            'status' => 'active',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ];

        $campaign = Campaign::create($campaignData);
        $campaign->refresh(); // Load default values from database

        $this->assertInstanceOf(Campaign::class, $campaign);
        $this->assertEquals('Test Campaign', $campaign->title);
        $this->assertEquals(1000.00, $campaign->target_amount);
        $this->assertEquals('active', $campaign->status);
        $this->assertEquals(0.00, $campaign->current_amount);
        $this->assertFalse($campaign->featured);
    }

    public function test_campaign_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $campaign->user);
        $this->assertEquals($user->id, $campaign->user->id);
    }

    public function test_campaign_belongs_to_category(): void
    {
        $category = CampaignCategory::factory()->create();
        $campaign = Campaign::factory()->create(['category_id' => $category->id]);

        $this->assertInstanceOf(CampaignCategory::class, $campaign->category);
        $this->assertEquals($category->id, $campaign->category->id);
    }

    public function test_campaign_has_many_donations(): void
    {
        $campaign = Campaign::factory()->create();
        $user = User::factory()->create();
        $donation = Donation::factory()->create([
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
        ]);

        $this->assertTrue($campaign->donations->contains($donation));
        $this->assertInstanceOf(Donation::class, $campaign->donations->first());
    }

    public function test_decimal_amounts_are_cast_correctly(): void
    {
        $campaign = Campaign::factory()->create([
            'target_amount' => '1500.50',
            'current_amount' => '750.25',
        ]);

        $this->assertEquals('1500.50', $campaign->target_amount);
        $this->assertEquals('750.25', $campaign->current_amount);
        // Laravel's decimal cast returns strings for precision
        $this->assertIsString($campaign->target_amount);
        $this->assertIsString($campaign->current_amount);
    }

    public function test_dates_are_cast_correctly(): void
    {
        $campaign = Campaign::factory()->create([
            'start_date' => '2025-01-15',
            'end_date' => '2025-03-15',
        ]);

        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $campaign->start_date);
        $this->assertInstanceOf(\Illuminate\Support\Carbon::class, $campaign->end_date);
        $this->assertEquals('2025-01-15', $campaign->start_date->format('Y-m-d'));
        $this->assertEquals('2025-03-15', $campaign->end_date->format('Y-m-d'));
    }

    public function test_featured_is_cast_to_boolean(): void
    {
        $campaign = Campaign::factory()->create(['featured' => '1']);

        $this->assertIsBool($campaign->featured);
        $this->assertTrue($campaign->featured);
    }

    public function test_campaign_progress_calculation(): void
    {
        $campaign = Campaign::factory()->create([
            'target_amount' => 1000.00,
            'current_amount' => 250.00,
        ]);

        // This would be a method we might add to the Campaign model
        $expectedProgress = 25.0; // 250/1000 * 100
        $this->assertEquals(25.0, ($campaign->current_amount / $campaign->target_amount) * 100);
    }

    public function test_campaign_status_defaults_to_draft(): void
    {
        $user = User::factory()->create();
        $category = CampaignCategory::factory()->create();

        $campaign = Campaign::create([
            'title' => 'Default Status Campaign',
            'description' => 'Testing default status',
            'target_amount' => 500.00,
            'start_date' => '2025-01-01',
            'end_date' => '2025-02-01',
            'category_id' => $category->id,
            'user_id' => $user->id,
        ]);
        $campaign->refresh(); // Load default values from database

        $this->assertEquals('draft', $campaign->status);
    }
}

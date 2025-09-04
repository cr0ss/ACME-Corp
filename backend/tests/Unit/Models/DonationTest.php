<?php

namespace Tests\Unit\Models;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_donation_can_be_created_with_required_fields(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $donationData = [
            'amount' => 100.50,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'payment_method' => 'credit_card',
            'status' => 'completed',
        ];

        $donation = Donation::create($donationData);
        $donation->refresh(); // Load default values from database

        $this->assertInstanceOf(Donation::class, $donation);
        $this->assertEquals('100.50', $donation->amount);
        $this->assertEquals('credit_card', $donation->payment_method);
        $this->assertEquals('completed', $donation->status);
        $this->assertFalse($donation->anonymous);
        $this->assertNull($donation->message);
    }

    public function test_donation_belongs_to_user(): void
    {
        $user = User::factory()->create();
        $donation = Donation::factory()->create(['user_id' => $user->id]);

        $this->assertInstanceOf(User::class, $donation->user);
        $this->assertEquals($user->id, $donation->user->id);
    }

    public function test_donation_belongs_to_campaign(): void
    {
        $campaign = Campaign::factory()->create();
        $donation = Donation::factory()->create(['campaign_id' => $campaign->id]);

        $this->assertInstanceOf(Campaign::class, $donation->campaign);
        $this->assertEquals($campaign->id, $donation->campaign->id);
    }

    public function test_donation_has_one_payment_transaction(): void
    {
        $donation = Donation::factory()->create();
        $transaction = PaymentTransaction::create([
            'donation_id' => $donation->id,
            'provider' => 'mock',
            'external_transaction_id' => 'TXN123',
            'amount' => $donation->amount,
            'currency' => 'USD',
            'status' => 'completed',
        ]);

        $this->assertInstanceOf(PaymentTransaction::class, $donation->paymentTransaction);
        $this->assertEquals($transaction->id, $donation->paymentTransaction->id);
    }

    public function test_amount_is_cast_to_decimal(): void
    {
        $donation = Donation::factory()->create(['amount' => '75.99']);

        $this->assertEquals('75.99', $donation->amount);
        $this->assertIsString($donation->amount);
    }

    public function test_anonymous_is_cast_to_boolean(): void
    {
        $donation = Donation::factory()->create(['anonymous' => '1']);

        $this->assertIsBool($donation->anonymous);
        $this->assertTrue($donation->anonymous);
    }

    public function test_donation_status_defaults_to_pending(): void
    {
        $user = User::factory()->create();
        $campaign = Campaign::factory()->create();

        $donation = Donation::create([
            'amount' => 50.00,
            'campaign_id' => $campaign->id,
            'user_id' => $user->id,
            'payment_method' => 'mock',
        ]);
        $donation->refresh(); // Load default values from database

        $this->assertEquals('pending', $donation->status);
    }

    public function test_donation_can_have_message(): void
    {
        $donation = Donation::factory()->create([
            'message' => 'This is for a great cause!',
        ]);

        $this->assertEquals('This is for a great cause!', $donation->message);
    }

    public function test_donation_can_be_anonymous(): void
    {
        $donation = Donation::factory()->create([
            'anonymous' => true,
            'message' => 'Anonymous donor message',
        ]);

        $this->assertTrue($donation->anonymous);
        $this->assertEquals('Anonymous donor message', $donation->message);
    }

    public function test_donation_can_have_transaction_id(): void
    {
        $donation = Donation::factory()->create([
            'transaction_id' => 'TXN_ABC123',
        ]);

        $this->assertEquals('TXN_ABC123', $donation->transaction_id);
    }
}

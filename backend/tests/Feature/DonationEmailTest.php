<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use App\Services\DonationService;
use App\Services\Payment\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DonationEmailTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Disable queue jobs for testing
        Queue::fake();

        // Fake mail for testing
        Mail::fake();
    }

    #[Test]
    public function it_sends_confirmation_email_to_donor_when_donation_is_created()
    {
        // Create test data
        $category = CampaignCategory::factory()->create();
        $campaignOwner = User::factory()->create();
        $donor = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $campaignOwner->id,
            'category_id' => $category->id,
            'status' => 'active',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
        ]);

        // Mock payment service to return success
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processPayment')
                ->andReturn(new \App\Contracts\PaymentResult(
                    success: true,
                    transactionId: 'TXN_'.uniqid(),
                    externalTransactionId: 'EXT_'.uniqid(),
                    responseData: ['status' => 'succeeded']
                ));
        });

        // Create donation service
        $donationService = app(DonationService::class);

        // Create donation
        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 50.00,
            'payment_method' => 'credit_card',
            'anonymous' => false,
            'message' => 'Great cause!',
        ];

        $donation = $donationService->createDonation($donationData, $donor);

        // Assert that the confirmation email was sent to the donor
        Mail::assertSent(\App\Mail\DonationConfirmationMail::class, function ($mail) use ($donor) {
            return $mail->hasTo($donor->email);
        });

        // Assert that the campaign owner notification was sent
        Mail::assertSent(\App\Mail\NewDonationMail::class, function ($mail) use ($campaignOwner) {
            return $mail->hasTo($campaignOwner->email);
        });
    }

    #[Test]
    public function it_does_not_send_campaign_owner_notification_when_donor_is_owner()
    {
        // Create test data
        $category = CampaignCategory::factory()->create();
        $user = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $user->id,
            'category_id' => $category->id,
            'status' => 'active',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
        ]);

        // Mock payment service to return success
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processPayment')
                ->andReturn(new \App\Contracts\PaymentResult(
                    success: true,
                    transactionId: 'TXN_'.uniqid(),
                    externalTransactionId: 'EXT_'.uniqid(),
                    responseData: ['status' => 'succeeded']
                ));
        });

        // Create donation service
        $donationService = app(DonationService::class);

        // Create donation (user donating to their own campaign)
        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 25.00,
            'payment_method' => 'credit_card',
            'anonymous' => false,
            'message' => 'Supporting my own cause!',
        ];

        $donation = $donationService->createDonation($donationData, $user);

        // Assert that the confirmation email was sent to the donor
        Mail::assertSent(\App\Mail\DonationConfirmationMail::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });

        // Assert that the campaign owner notification was NOT sent (same person)
        Mail::assertNotSent(\App\Mail\NewDonationMail::class);
    }

    #[Test]
    public function it_handles_email_sending_errors_gracefully()
    {
        // Create test data
        $category = CampaignCategory::factory()->create();
        $campaignOwner = User::factory()->create();
        $donor = User::factory()->create();

        $campaign = Campaign::factory()->create([
            'user_id' => $campaignOwner->id,
            'category_id' => $category->id,
            'status' => 'active',
            'start_date' => now()->subDays(1),
            'end_date' => now()->addDays(30),
        ]);

        // Mock payment service to return success
        $this->mock(PaymentService::class, function ($mock) {
            $mock->shouldReceive('processPayment')
                ->andReturn(new \App\Contracts\PaymentResult(
                    success: true,
                    transactionId: 'TXN_'.uniqid(),
                    externalTransactionId: 'EXT_'.uniqid(),
                    responseData: ['status' => 'succeeded']
                ));
        });

        // Mock mail to throw an exception
        Mail::shouldReceive('to')
            ->andThrow(new \Exception('Mail server error'));

        // Create donation service
        $donationService = app(DonationService::class);

        // Create donation - should not crash even if email fails
        $donationData = [
            'campaign_id' => $campaign->id,
            'amount' => 100.00,
            'payment_method' => 'credit_card',
            'anonymous' => false,
            'message' => 'Test donation',
        ];

        $donation = $donationService->createDonation($donationData, $donor);

        // Assert that the donation was still created successfully
        $this->assertDatabaseHas('donations', [
            'id' => $donation->id,
            'amount' => 100.00,
            'status' => 'pending',
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use App\Services\Payment\PaymentService;
use Illuminate\Support\Facades\DB;

class DonationService
{
    public function __construct(
        private PaymentService $paymentService,
        private NotificationService $notificationService
    ) {}

    /**
     * Create and process a donation.
     */
    public function createDonation(array $data, User $donor): Donation
    {
        $campaign = Campaign::findOrFail($data['campaign_id']);

        // Validate campaign status
        $this->validateCampaignForDonation($campaign);

        return DB::transaction(function () use ($data, $donor) {
            // Create donation record
            $donation = Donation::create([
                'amount' => $data['amount'],
                'campaign_id' => $data['campaign_id'],
                'user_id' => $donor->id,
                'payment_method' => $data['payment_method'],
                'status' => 'pending',
                'anonymous' => $data['anonymous'] ?? false,
                'message' => $data['message'] ?? null,
            ]);

            // Log donation creation
            AuditLog::createLog(
                $donor->id,
                'donation_created',
                'App\Models\Donation',
                $donation->id,
                null,
                $donation->toArray(),
                request()?->ip(),
                request()?->userAgent()
            );

            // Process payment
            try {
                $paymentResult = $this->paymentService->processPayment(
                    $donation,
                    $data,
                    $data['provider'] ?? null
                );

                if ($paymentResult->isSuccess()) {
                    // Send confirmation notifications
                    $this->notificationService->sendDonationConfirmation($donation);
                    $this->notificationService->sendCampaignOwnerNotification($donation);
                }

                return $donation->fresh();
            } catch (\Exception $e) {
                // Mark donation as failed
                $donation->update(['status' => 'failed']);
                
                // Log the error
                AuditLog::createLog(
                    $donor->id,
                    'donation_failed',
                    'App\Models\Donation',
                    $donation->id,
                    null,
                    ['error' => $e->getMessage()],
                    request()?->ip(),
                    request()?->userAgent()
                );

                throw $e;
            }
        });
    }

    /**
     * Refund a donation.
     */
    public function refundDonation(Donation $donation, User $user, ?string $reason = null): bool
    {
        if ($donation->status !== 'completed') {
            throw new \Exception('Can only refund completed donations');
        }

        // Check refund eligibility
        $refundTimeLimit = config('payment.refund.time_limit_days', 30);
        if ($donation->created_at->addDays($refundTimeLimit)->isPast()) {
            throw new \Exception("Refund period of {$refundTimeLimit} days has expired");
        }

        return DB::transaction(function () use ($donation, $user, $reason) {
            $paymentResult = $this->paymentService->refundPayment($donation);

            if ($paymentResult->isSuccess()) {
                // Log refund
                AuditLog::createLog(
                    $user->id,
                    'donation_refunded',
                    'App\Models\Donation',
                    $donation->id,
                    ['status' => 'completed'],
                    ['status' => 'refunded', 'reason' => $reason],
                    request()?->ip(),
                    request()?->userAgent()
                );

                // Send refund notification
                $this->notificationService->sendRefundNotification($donation);

                return true;
            }

            return false;
        });
    }

    /**
     * Get donation receipt data.
     */
    public function getDonationReceipt(Donation $donation): array
    {
        if ($donation->status !== 'completed') {
            throw new \Exception('Receipt is only available for completed donations');
        }

        $donation->load(['campaign', 'campaign.category', 'user', 'paymentTransaction']);

        return [
            'receipt_id' => 'RCP_' . $donation->id . '_' . $donation->created_at->format('Ymd'),
            'donation' => [
                'id' => $donation->id,
                'amount' => $donation->amount,
                'currency' => 'USD',
                'date' => $donation->created_at->toDateString(),
                'time' => $donation->created_at->toTimeString(),
                'payment_method' => $donation->payment_method,
                'transaction_id' => $donation->transaction_id,
                'message' => $donation->message,
                'anonymous' => $donation->anonymous,
            ],
            'campaign' => [
                'id' => $donation->campaign->id,
                'title' => $donation->campaign->title,
                'category' => $donation->campaign->category->name,
            ],
            'donor' => [
                'name' => $donation->anonymous ? 'Anonymous' : $donation->user->name,
                'employee_id' => $donation->anonymous ? null : $donation->user->employee_id,
            ],
            'organization' => [
                'name' => 'ACME Corporation',
                'address' => '123 Business Street, Corporate City, CC 12345',
                'tax_id' => 'TAX123456789',
                'phone' => '+1 (555) 123-4567',
                'email' => 'csr@acme.com',
            ],
            'tax_info' => [
                'deductible' => true,
                'tax_year' => $donation->created_at->year,
                'irs_section' => '501(c)(3)',
            ],
            'issued_at' => now()->toISOString(),
        ];
    }

    /**
     * Get donation statistics for a user.
     */
    public function getUserDonationStats(User $user): array
    {
        /** @var \Illuminate\Database\Eloquent\Builder<\App\Models\Donation> $donations */
        $donations = $user->donations()->where('status', 'completed');
        
        return [
            'total_donations' => $donations->count(),
            'total_amount' => $donations->sum('amount'),
            'average_amount' => $donations->avg('amount') ?? 0,
            'campaigns_supported' => $donations->distinct('campaign_id')->count(),
            'first_donation' => $donations->oldest()->first()?->created_at?->toDateTimeString(),
            'last_donation' => $donations->latest()->first()?->created_at?->toDateTimeString(),
            'favorite_category' => $this->getFavoriteDonationCategory($user),
        ];
    }

    /**
     * Get donation statistics for a campaign.
     */
    public function getCampaignDonationStats(Campaign $campaign): array
    {
        $donations = $campaign->donations()->where('status', 'completed');
        
        return [
            'total_donations' => $donations->count(),
            'total_amount' => $donations->sum('amount'),
            'average_amount' => $donations->avg('amount') ?? 0,
            'unique_donors' => $donations->distinct('user_id')->count(),
            'anonymous_donations' => $donations->where('anonymous', true)->count(),
            'recent_donations' => $donations->where('created_at', '>=', now()->subDays(7))->count(),
            'top_donation' => $donations->max('amount') ?? 0,
        ];
    }

    /**
     * Get recent donations for a campaign.
     */
    public function getRecentDonations(Campaign $campaign, int $limit = 10): \Illuminate\Support\Collection
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Donation> $donationsCollection */
        $donationsCollection = $campaign->donations()
            ->with(['user'])
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
            
        return $donationsCollection->map(function (\App\Models\Donation $donation) {
                return [
                    'id' => $donation->id,
                    'amount' => $donation->amount,
                    'donor_name' => $donation->anonymous ? 'Anonymous' : $donation->user->name,
                    'message' => $donation->message,
                    'created_at' => $donation->created_at,
                ];
            });
    }

    /**
     * Check if user can refund donation.
     */
    public function canRefundDonation(Donation $donation, User $user): bool
    {
        // Only admins or the donor can request refunds
        if (!$user->is_admin && $donation->user_id !== $user->id) {
            return false;
        }

        // Must be completed donation
        if ($donation->status !== 'completed') {
            return false;
        }

        // Check time limit
        $refundTimeLimit = config('payment.refund.time_limit_days', 30);
        return $donation->created_at->addDays($refundTimeLimit)->isFuture();
    }

    /**
     * Validate campaign for donation.
     */
    private function validateCampaignForDonation(Campaign $campaign): void
    {
        if ($campaign->status !== 'active') {
            throw new \Exception('This campaign is not currently accepting donations');
        }

        if ($campaign->end_date < now()) {
            throw new \Exception('This campaign has ended');
        }

        if ($campaign->start_date > now()) {
            throw new \Exception('This campaign has not started yet');
        }
    }

    /**
     * Get user's favorite donation category.
     */
    private function getFavoriteDonationCategory(User $user): ?string
    {
        /** @var object{name: string, donation_count: int}|null $categoryStats */
        $categoryStats = $user->donations()
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->join('campaign_categories', 'campaigns.category_id', '=', 'campaign_categories.id')
            ->where('donations.status', 'completed')
            ->selectRaw('campaign_categories.name, COUNT(*) as donation_count')
            ->groupBy('campaign_categories.name')
            ->orderBy('donation_count', 'desc')
            ->first();

        return $categoryStats?->name;
    }
}

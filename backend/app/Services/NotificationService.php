<?php

namespace App\Services;

use App\Models\Donation;
use App\Models\User;
use App\Models\Campaign;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;

class NotificationService
{
    /**
     * Send donation confirmation email to donor.
     */
    public function sendDonationConfirmation(Donation $donation): void
    {
        try {
            $donation->load(['campaign', 'user']);
            
            // TODO: Implement actual email sending
            // For now, just log the notification
            Log::info('Donation confirmation email sent', [
                'donation_id' => $donation->id,
                'donor_email' => $donation->user->email,
                'amount' => $donation->amount,
                'campaign' => $donation->campaign->title,
            ]);

            // In a real implementation, you would use Laravel's Mail facade:
            // Mail::to($donation->user->email)->send(new DonationConfirmationMail($donation));
            
        } catch (\Exception $e) {
            Log::error('Failed to send donation confirmation email', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send notification to campaign owner about new donation.
     */
    public function sendCampaignOwnerNotification(Donation $donation): void
    {
        try {
            $donation->load(['campaign.user', 'user']);
            
            // Don't notify if donor and campaign owner are the same
            if ($donation->user_id === $donation->campaign->user_id) {
                return;
            }

            Log::info('Campaign owner notification sent', [
                'donation_id' => $donation->id,
                'campaign_owner_email' => $donation->campaign->user->email,
                'donor_name' => $donation->anonymous ? 'Anonymous' : $donation->user->name,
                'amount' => $donation->amount,
                'campaign' => $donation->campaign->title,
            ]);

            // In a real implementation:
            // Mail::to($donation->campaign->user->email)->send(new NewDonationMail($donation));
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign owner notification', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send refund notification to donor.
     */
    public function sendRefundNotification(Donation $donation): void
    {
        try {
            $donation->load(['user', 'campaign']);
            
            Log::info('Refund notification sent', [
                'donation_id' => $donation->id,
                'donor_email' => $donation->user->email,
                'refund_amount' => $donation->amount,
                'campaign' => $donation->campaign->title,
            ]);

            // In a real implementation:
            // Mail::to($donation->user->email)->send(new RefundNotificationMail($donation));
            
        } catch (\Exception $e) {
            Log::error('Failed to send refund notification', [
                'donation_id' => $donation->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send campaign approval notification.
     */
    public function sendCampaignApprovalNotification(Campaign $campaign): void
    {
        try {
            $campaign->load(['user']);
            
            Log::info('Campaign approval notification sent', [
                'campaign_id' => $campaign->id,
                'owner_email' => $campaign->user->email,
                'campaign_title' => $campaign->title,
            ]);

            // In a real implementation:
            // Mail::to($campaign->user->email)->send(new CampaignApprovedMail($campaign));
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign approval notification', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send campaign rejection notification.
     */
    public function sendCampaignRejectionNotification(Campaign $campaign, ?string $reason = null): void
    {
        try {
            $campaign->load(['user']);
            
            Log::info('Campaign rejection notification sent', [
                'campaign_id' => $campaign->id,
                'owner_email' => $campaign->user->email,
                'campaign_title' => $campaign->title,
                'reason' => $reason,
            ]);

            // In a real implementation:
            // Mail::to($campaign->user->email)->send(new CampaignRejectedMail($campaign, $reason));
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign rejection notification', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send campaign milestone notification (e.g., 50% funded, goal reached).
     */
    public function sendCampaignMilestoneNotification(Campaign $campaign, string $milestone): void
    {
        try {
            $campaign->load(['user']);
            
            Log::info('Campaign milestone notification sent', [
                'campaign_id' => $campaign->id,
                'owner_email' => $campaign->user->email,
                'campaign_title' => $campaign->title,
                'milestone' => $milestone,
                'progress' => $campaign->progress_percentage,
            ]);

            // In a real implementation:
            // Mail::to($campaign->user->email)->send(new CampaignMilestoneMail($campaign, $milestone));
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign milestone notification', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send campaign ending soon notification.
     */
    public function sendCampaignEndingSoonNotification(Campaign $campaign): void
    {
        try {
            $campaign->load(['user']);
            
            Log::info('Campaign ending soon notification sent', [
                'campaign_id' => $campaign->id,
                'owner_email' => $campaign->user->email,
                'campaign_title' => $campaign->title,
                'end_date' => $campaign->end_date,
            ]);

            // In a real implementation:
            // Mail::to($campaign->user->email)->send(new CampaignEndingSoonMail($campaign));
            
        } catch (\Exception $e) {
            Log::error('Failed to send campaign ending soon notification', [
                'campaign_id' => $campaign->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send weekly digest to all users.
     */
    public function sendWeeklyDigest(): void
    {
        try {
            $users = User::where('role', 'employee')->get();
            
            foreach ($users as $user) {
                $this->sendUserWeeklyDigest($user);
            }
            
        } catch (\Exception $e) {
            Log::error('Failed to send weekly digest', [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send weekly digest to a specific user.
     */
    private function sendUserWeeklyDigest(User $user): void
    {
        try {
            // Get trending campaigns
            $trendingCampaigns = Campaign::with(['category'])
                ->where('status', 'active')
                ->where('start_date', '<=', now())
                ->where('end_date', '>=', now())
                ->withCount(['donations' => function ($query) {
                    $query->where('status', 'completed')
                          ->where('created_at', '>=', now()->subDays(7));
                }])
                ->orderBy('donations_count', 'desc')
                ->limit(5)
                ->get();

            Log::info('Weekly digest sent', [
                'user_id' => $user->id,
                'user_email' => $user->email,
                'trending_campaigns_count' => $trendingCampaigns->count(),
            ]);

            // In a real implementation:
            // Mail::to($user->email)->send(new WeeklyDigestMail($user, $trendingCampaigns));
            
        } catch (\Exception $e) {
            Log::error('Failed to send weekly digest to user', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send admin notification about suspicious activity.
     */
    public function sendSuspiciousActivityNotification(string $activity, array $details): void
    {
        try {
            $admins = User::where('is_admin', true)->get();
            
            foreach ($admins as $admin) {
                Log::warning('Suspicious activity notification sent to admin', [
                    'admin_id' => $admin->id,
                    'admin_email' => $admin->email,
                    'activity' => $activity,
                    'details' => $details,
                ]);
            }

            // In a real implementation:
            // foreach ($admins as $admin) {
            //     Mail::to($admin->email)->send(new SuspiciousActivityMail($activity, $details));
            // }
            
        } catch (\Exception $e) {
            Log::error('Failed to send suspicious activity notification', [
                'activity' => $activity,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

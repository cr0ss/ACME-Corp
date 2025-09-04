<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignService
{
    /**
     * Create a new campaign.
     *
     * @param array<string, mixed> $data
     */
    public function createCampaign(array $data, User $creator): Campaign
    {
        return DB::transaction(function () use ($data, $creator) {
            $campaign = Campaign::create([
                ...$data,
                'user_id' => $creator->id,
                'status' => 'draft',
                'current_amount' => 0,
            ]);

            // Log campaign creation
            AuditLog::createLog(
                $creator->id,
                'campaign_created',
                'App\Models\Campaign',
                $campaign->id,
                null,
                $campaign->toArray(),
                request()->ip(),
                request()->userAgent()
            );

            return $campaign;
        });
    }

    /**
     * Update a campaign.
     *
     * @param array<string, mixed> $data
     */
    public function updateCampaign(Campaign $campaign, array $data, User $user): Campaign|null
    {
        return DB::transaction(function () use ($campaign, $data, $user) {
            $oldValues = $campaign->toArray();
            
            $campaign->update($data);

            // Log campaign update
            $freshCampaign = $campaign->fresh();
            AuditLog::createLog(
                $user->id,
                'campaign_updated',
                'App\Models\Campaign',
                $campaign->id,
                $oldValues,
                $freshCampaign ? $freshCampaign->toArray() : [],
                request()->ip(),
                request()->userAgent()
            );

            return $campaign->fresh();
        });
    }

    /**
     * Delete a campaign.
     */
    public function deleteCampaign(Campaign $campaign, User $user): bool|null
    {
        // Check if campaign can be deleted
        if ($campaign->donations()->count() > 0) {
            throw new \Exception('Cannot delete campaign with existing donations');
        }

        return DB::transaction(function () use ($campaign, $user) {
            // Log campaign deletion
            AuditLog::createLog(
                $user->id,
                'campaign_deleted',
                'App\Models\Campaign',
                $campaign->id,
                $campaign->toArray(),
                null,
                request()->ip(),
                request()->userAgent()
            );

            return $campaign->delete();
        });
    }

    /**
     * Approve a campaign (admin only).
     */
    public function approveCampaign(Campaign $campaign, User $admin): Campaign|null
    {
        if (!$admin->is_admin) {
            throw new \Exception('Only administrators can approve campaigns');
        }

        return $this->updateCampaignStatus($campaign, 'active', $admin);
    }

    /**
     * Reject a campaign (admin only).
     */
    public function rejectCampaign(Campaign $campaign, User $admin, ?string $reason = null): Campaign|null
    {
        if (!$admin->is_admin) {
            throw new \Exception('Only administrators can reject campaigns');
        }

        return $this->updateCampaignStatus($campaign, 'cancelled', $admin, $reason);
    }

    /**
     * Feature/unfeature a campaign (admin only).
     */
    public function toggleFeatured(Campaign $campaign, User $admin, bool $featured = true): Campaign|null
    {
        if (!$admin->is_admin) {
            throw new \Exception('Only administrators can feature campaigns');
        }

        return DB::transaction(function () use ($campaign, $admin, $featured) {
            $oldValues = ['featured' => $campaign->featured];
            
            $campaign->update(['featured' => $featured]);

            // Log campaign feature toggle
            AuditLog::createLog(
                $admin->id,
                $featured ? 'campaign_featured' : 'campaign_unfeatured',
                'App\Models\Campaign',
                $campaign->id,
                $oldValues,
                ['featured' => $featured],
                request()->ip(),
                request()->userAgent()
            );

            return $campaign->fresh();
        });
    }

    /**
     * Get campaign statistics.
     *
     * @return array<string, mixed>
     */
    public function getCampaignStats(Campaign $campaign): array
    {
        $donations = $campaign->donations()->where('status', 'completed');
        
        return [
            'total_donations' => $donations->count(),
            'total_donated' => $donations->sum('amount'),
            'progress_percentage' => $campaign->progress_percentage,
            'days_remaining' => max(0, now()->diffInDays($campaign->end_date, false)),
            'is_active' => $campaign->is_active,
            'average_donation' => $donations->avg('amount') ?? 0,
            'unique_donors' => $donations->distinct('user_id')->count(),
        ];
    }

    /**
     * Get trending campaigns.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Campaign>
     */
    public function getTrendingCampaigns(int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Campaign::with(['category', 'user'])
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->withCount(['donations' => function ($query): void {
                $query->where('status', 'completed')
                      ->where('created_at', '>=', now()->subDays(7)); // Last 7 days
            }])
            ->orderBy('donations_count', 'desc')
            ->orderBy('current_amount', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Get campaigns ending soon.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\Campaign>
     */
    public function getCampaignsEndingSoon(int $days = 7, int $limit = 10): \Illuminate\Database\Eloquent\Collection
    {
        return Campaign::with(['category', 'user'])
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays($days))
            ->orderBy('end_date', 'asc')
            ->limit($limit)
            ->get();
    }

    /**
     * Check if user can edit campaign.
     */
    public function canUserEditCampaign(Campaign $campaign, User $user): bool
    {
        return $user->is_admin || $campaign->user_id === $user->id;
    }

    /**
     * Update campaign status.
     */
    private function updateCampaignStatus(Campaign $campaign, string $status, User $user, ?string $reason = null): Campaign|null
    {
        return DB::transaction(function () use ($campaign, $status, $user, $reason) {
            $oldValues = ['status' => $campaign->status];
            
            $campaign->update(['status' => $status]);

            $newValues = ['status' => $status];
            if ($reason) {
                $newValues['reason'] = $reason;
            }

            // Log status change
            AuditLog::createLog(
                $user->id,
                'campaign_status_changed',
                'App\Models\Campaign',
                $campaign->id,
                $oldValues,
                $newValues,
                request()->ip(),
                request()->userAgent()
            );

            return $campaign->fresh();
        });
    }
}

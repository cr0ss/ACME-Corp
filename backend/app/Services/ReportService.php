<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Generate donation summary report.
     */
    public function getDonationSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $donations = Donation::where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalDonations = $donations->count();
        $totalAmount = $donations->sum('amount');
        $avgDonation = $donations->avg('amount') ?? 0;
        $uniqueDonors = $donations->distinct('user_id')->count();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_donations' => $totalDonations,
                'total_amount' => round($totalAmount, 2),
                'average_donation' => round($avgDonation, 2),
                'unique_donors' => $uniqueDonors,
                'donations_per_donor' => $uniqueDonors > 0 ? round($totalDonations / $uniqueDonors, 2) : 0,
            ],
            'trends' => $this->getDonationTrends($startDate, $endDate),
        ];
    }

    /**
     * Generate campaign performance report.
     */
    public function getCampaignPerformance(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $campaigns = Campaign::whereBetween('created_at', [$startDate, $endDate]);

        $totalCampaigns = $campaigns->count();
        $activeCampaigns = $campaigns->where('status', 'active')->count();
        $completedCampaigns = $campaigns->where('status', 'completed')->count();
        $cancelledCampaigns = $campaigns->where('status', 'cancelled')->count();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_campaigns' => $totalCampaigns,
                'active_campaigns' => $activeCampaigns,
                'completed_campaigns' => $completedCampaigns,
                'cancelled_campaigns' => $cancelledCampaigns,
                'success_rate' => $totalCampaigns > 0 ? round(($completedCampaigns / $totalCampaigns) * 100, 2) : 0,
            ],
            'top_campaigns' => $this->getTopCampaigns($startDate, $endDate),
            'category_breakdown' => $this->getCategoryBreakdown($startDate, $endDate),
        ];
    }

    /**
     * Generate user engagement report.
     */
    public function getUserEngagement(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $totalUsers = User::count();
        $donorsInPeriod = Donation::whereBetween('created_at', [$startDate, $endDate])
            ->where('status', 'completed')
            ->distinct('user_id')
            ->count();

        $campaignCreators = Campaign::whereBetween('created_at', [$startDate, $endDate])
            ->distinct('user_id')
            ->count();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_users' => $totalUsers,
                'active_donors' => $donorsInPeriod,
                'campaign_creators' => $campaignCreators,
                'donor_participation_rate' => $totalUsers > 0 ? round(($donorsInPeriod / $totalUsers) * 100, 2) : 0,
            ],
            'department_breakdown' => $this->getDepartmentBreakdown($startDate, $endDate),
            'top_donors' => $this->getTopDonors($startDate, $endDate),
        ];
    }

    /**
     * Generate financial report.
     */
    public function getFinancialReport(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $donations = Donation::whereBetween('created_at', [$startDate, $endDate]);
        $completedDonations = $donations->where('status', 'completed');
        $refundedDonations = $donations->where('status', 'refunded');

        $grossAmount = $completedDonations->sum('amount');
        $refundedAmount = $refundedDonations->sum('amount');
        $netAmount = $grossAmount - $refundedAmount;

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'financial_summary' => [
                'gross_amount' => round($grossAmount, 2),
                'refunded_amount' => round($refundedAmount, 2),
                'net_amount' => round($netAmount, 2),
                'refund_rate' => $grossAmount > 0 ? round(($refundedAmount / $grossAmount) * 100, 2) : 0,
            ],
            'payment_methods' => $this->getPaymentMethodBreakdown($startDate, $endDate),
            'daily_revenue' => $this->getDailyRevenue($startDate, $endDate),
        ];
    }

    /**
     * Export donations data for external reporting.
     */
    public function exportDonations(?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        return Donation::with(['user', 'campaign', 'campaign.category'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($donation) {
                return [
                    'donation_id' => $donation->id,
                    'donation_date' => $donation->created_at->toDateString(),
                    'amount' => $donation->amount,
                    'status' => $donation->status,
                    'payment_method' => $donation->payment_method,
                    'anonymous' => $donation->anonymous,
                    'donor_name' => $donation->anonymous ? 'Anonymous' : $donation->user->name,
                    'donor_email' => $donation->anonymous ? null : $donation->user->email,
                    'donor_department' => $donation->anonymous ? null : $donation->user->department,
                    'campaign_title' => $donation->campaign->title,
                    'campaign_category' => $donation->campaign->category->name,
                    'campaign_owner' => $donation->campaign->user->name,
                ];
            });
    }

    /**
     * Get donation trends over time.
     */
    private function getDonationTrends(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $donations = DB::table('donations')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as amount')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $donations->map(function ($item) {
            return [
                'date' => $item->date,
                'donations_count' => (int) $item->count,
                'total_amount' => round((float) $item->amount, 2),
            ];
        })->toArray();
    }

    /**
     * Get top performing campaigns.
     */
    private function getTopCampaigns(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 10): Collection
    {
        return Campaign::with(['category', 'user'])
            ->withSum(['donations' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'amount')
            ->withCount(['donations' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                      ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderBy('donations_sum_amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'category' => $campaign->category->name,
                    'owner' => $campaign->user->name,
                    'total_raised' => round($campaign->donations_sum_amount ?? 0, 2),
                    'donation_count' => $campaign->donations_count ?? 0,
                    'progress_percentage' => $campaign->progress_percentage,
                ];
            });
    }

    /**
     * Get donation breakdown by category.
     */
    private function getCategoryBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        return DB::table('donations')
            ->join('campaigns', 'donations.campaign_id', '=', 'campaigns.id')
            ->join('campaign_categories', 'campaigns.category_id', '=', 'campaign_categories.id')
            ->select(
                'campaign_categories.name as category',
                DB::raw('COUNT(donations.id) as donation_count'),
                DB::raw('SUM(donations.amount) as total_amount')
            )
            ->where('donations.status', 'completed')
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('campaign_categories.id', 'campaign_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get donation breakdown by department.
     */
    private function getDepartmentBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        return DB::table('donations')
            ->join('users', 'donations.user_id', '=', 'users.id')
            ->select(
                'users.department',
                DB::raw('COUNT(donations.id) as donation_count'),
                DB::raw('SUM(donations.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT donations.user_id) as unique_donors')
            )
            ->where('donations.status', 'completed')
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('users.department')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'department' => $item->department,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                    'unique_donors' => (int) $item->unique_donors,
                ];
            });
    }

    /**
     * Get top donors.
     */
    private function getTopDonors(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 10): Collection
    {
        return DB::table('donations')
            ->join('users', 'donations.user_id', '=', 'users.id')
            ->select(
                'users.name',
                'users.department',
                DB::raw('COUNT(donations.id) as donation_count'),
                DB::raw('SUM(donations.amount) as total_amount')
            )
            ->where('donations.status', 'completed')
            ->where('donations.anonymous', false)
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('users.id', 'users.name', 'users.department')
            ->orderBy('total_amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item) {
                return [
                    'name' => $item->name,
                    'department' => $item->department,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get payment method breakdown.
     */
    private function getPaymentMethodBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        return DB::table('donations')
            ->select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total_amount')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->map(function ($item) {
                return [
                    'payment_method' => $item->payment_method,
                    'count' => (int) $item->count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get daily revenue data.
     */
    private function getDailyRevenue(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $revenue = DB::table('donations')
            ->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('SUM(amount) as revenue')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date')
            ->get();

        return $revenue->map(function ($item) {
            return [
                'date' => $item->date,
                'revenue' => round((float) $item->revenue, 2),
            ];
        })->toArray();
    }
}

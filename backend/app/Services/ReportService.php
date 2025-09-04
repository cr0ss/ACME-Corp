<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /**
     * Generate donation summary report.
     *
     * @return array<string, mixed>
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
                'total_amount' => round((float) $totalAmount, 2),
                'average_donation' => round((float) $avgDonation, 2),
                'unique_donors' => $uniqueDonors,
                'donations_per_donor' => $uniqueDonors > 0 ? round($totalDonations / $uniqueDonors, 2) : 0,
            ],
            'trends' => $this->getDonationTrends($startDate, $endDate),
        ];
    }

    /**
     * Generate campaign performance report.
     *
     * @return array<string, mixed>
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
                'success_rate' => $totalCampaigns > 0 ? round((float) (($completedCampaigns / $totalCampaigns) * 100), 2) : 0,
            ],
            'top_campaigns' => $this->getTopCampaigns($startDate, $endDate),
            'category_breakdown' => $this->getCategoryBreakdown($startDate, $endDate),
        ];
    }

    /**
     * Generate user engagement report.
     *
     * @return array<string, mixed>
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
                'donor_participation_rate' => $totalUsers > 0 ? round((float) (($donorsInPeriod / $totalUsers) * 100), 2) : 0,
            ],
            'department_breakdown' => $this->getDepartmentBreakdown($startDate, $endDate),
            'top_donors' => $this->getTopDonors($startDate, $endDate),
        ];
    }

    /**
     * Generate financial report.
     *
     * @return array<string, mixed>
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
                'gross_amount' => round((float) $grossAmount, 2),
                'refunded_amount' => round((float) $refundedAmount, 2),
                'net_amount' => round((float) $netAmount, 2),
                'refund_rate' => $grossAmount > 0 ? round((float) (($refundedAmount / $grossAmount) * 100), 2) : 0,
            ],
            'payment_methods' => $this->getPaymentMethodBreakdown($startDate, $endDate),
            'daily_revenue' => $this->getDailyRevenue($startDate, $endDate),
        ];
    }

    /**
     * Export donations data for external reporting.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    /**
     * @return \Illuminate\Support\Collection<int, array{donation_id: int, donation_date: string, amount: float, status: string, payment_method: string, anonymous: bool, donor_name: string, donor_email: string|null, donor_department: string|null, campaign_title: string, campaign_category: string, campaign_owner: string}>
     */
    public function exportDonations(?string $startDate = null, ?string $endDate = null): Collection
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        return Donation::with(['user', 'campaign', 'campaign.category'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function ($donation): array {
                return [
                    'donation_id' => $donation->id,
                    'donation_date' => $donation->created_at?->toDateString() ?? 'Unknown',
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
     *
     * @return array<int, array<string, mixed>>
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

        return $donations->map(function ($item): array {
            return [
                'date' => $item->date,
                'donations_count' => (int) $item->count,
                'total_amount' => round((float) $item->amount, 2),
            ];
        })->toArray();
    }

    /**
     * Get top performing campaigns.
     *
     * @return \Illuminate\Support\Collection<int, array{id: int, title: string, category: string, owner: string, total_raised: float, donation_count: int, progress_percentage: float}>
     */
    private function getTopCampaigns(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 10): Collection
    {
        return Campaign::with(['category', 'user'])
            ->withSum(['donations' => function ($query) use ($startDate, $endDate): void {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'amount')
            ->withCount(['donations' => function ($query) use ($startDate, $endDate): void {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->orderBy('donations_sum_amount', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($campaign): array {
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
     *
     * @return \Illuminate\Support\Collection<int, array{category: string, donation_count: int, total_amount: float}>
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
            ->map(function ($item): array {
                return [
                    'category' => (string) $item->category,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get donation breakdown by department.
     *
     * @return \Illuminate\Support\Collection<int, array{department: string, donation_count: int, total_amount: float, unique_donors: int}>
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
            ->map(function ($item): array {
                return [
                    'department' => (string) $item->department,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                    'unique_donors' => (int) $item->unique_donors,
                ];
            });
    }

    /**
     * Get top donors.
     *
     * @return \Illuminate\Support\Collection<int, array{name: string, department: string, donation_count: int, total_amount: float}>
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
            ->map(function ($item): array {
                return [
                    'name' => (string) $item->name,
                    'department' => (string) $item->department,
                    'donation_count' => (int) $item->donation_count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get payment method breakdown.
     *
     * @return \Illuminate\Support\Collection<int, array{payment_method: string, count: int, total_amount: float}>
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
            ->map(function ($item): array {
                return [
                    'payment_method' => (string) $item->payment_method,
                    'count' => (int) $item->count,
                    'total_amount' => round((float) $item->total_amount, 2),
                ];
            });
    }

    /**
     * Get daily revenue data.
     *
     * @return array<int, array{date: string, revenue: float}>
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

        return $revenue->map(function ($item): array {
            return [
                'date' => $item->date,
                'revenue' => round((float) $item->revenue, 2),
            ];
        })->toArray();
    }
}

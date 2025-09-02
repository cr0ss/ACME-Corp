<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    /**
     * Get dashboard overview statistics.
     */
    public function dashboard()
    {
        $stats = [
            'overview' => [
                'total_users' => User::count(),
                'active_users' => User::whereHas('donations', function ($query) {
                    $query->where('created_at', '>=', now()->subDays(30));
                })->count(),
                'total_campaigns' => Campaign::count(),
                'active_campaigns' => Campaign::where('status', 'active')->count(),
                'total_donations' => Donation::where('status', 'completed')->count(),
                'total_raised' => Donation::where('status', 'completed')->sum('amount'),
                'avg_donation' => Donation::where('status', 'completed')->avg('amount'),
                'success_rate' => $this->calculateSuccessRate(),
            ],
            'recent_activity' => [
                'recent_donations' => Donation::with(['user', 'campaign'])
                    ->where('status', 'completed')
                    ->orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(),
                'recent_campaigns' => Campaign::with(['user', 'category'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get(),
                'recent_users' => User::orderBy('created_at', 'desc')
                    ->limit(10)
                    ->get(['id', 'name', 'email', 'department', 'employee_id', 'created_at']),
            ],
            'trends' => [
                'monthly_donations' => $this->getMonthlyDonationTrends(),
                'top_categories' => $this->getTopCategories(),
                'top_donors' => $this->getTopDonors(),
                'campaign_performance' => $this->getCampaignPerformance(),
            ],
        ];

        return response()->json($stats);
    }

    /**
     * Get detailed donation analytics.
     */
    public function donationAnalytics(Request $request)
    {
        $period = $request->get('period', '30'); // days
        $groupBy = $request->get('group_by', 'day'); // day, week, month

        $analytics = [
            'summary' => [
                'total_donations' => Donation::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->count(),
                'total_amount' => Donation::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->sum('amount'),
                'avg_donation' => Donation::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->avg('amount'),
                'unique_donors' => Donation::where('status', 'completed')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->distinct('user_id')
                    ->count(),
            ],
            'trends' => $this->getDonationTrends($period, $groupBy),
            'by_payment_method' => $this->getDonationsByPaymentMethod($period),
            'by_department' => $this->getDonationsByDepartment($period),
            'hourly_distribution' => $this->getHourlyDonationDistribution($period),
        ];

        return response()->json($analytics);
    }

    /**
     * Get campaign analytics.
     */
    public function campaignAnalytics(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $analytics = [
            'summary' => [
                'total_campaigns' => Campaign::where('created_at', '>=', now()->subDays($period))->count(),
                'success_rate' => $this->getCampaignSuccessRate($period),
                'avg_target' => Campaign::where('created_at', '>=', now()->subDays($period))->avg('target_amount'),
                'avg_raised' => Campaign::where('created_at', '>=', now()->subDays($period))->avg('current_amount'),
            ],
            'performance' => [
                'top_campaigns' => Campaign::with(['category', 'user'])
                    ->where('created_at', '>=', now()->subDays($period))
                    ->orderBy('current_amount', 'desc')
                    ->limit(10)
                    ->get(),
                'by_category' => $this->getCampaignsByCategory($period),
                'completion_rates' => $this->getCampaignCompletionRates($period),
            ],
            'engagement' => [
                'most_donations' => Campaign::with(['category'])
                    ->withCount('donations')
                    ->where('created_at', '>=', now()->subDays($period))
                    ->orderBy('donations_count', 'desc')
                    ->limit(10)
                    ->get(),
            ],
        ];

        return response()->json($analytics);
    }

    /**
     * Get user engagement analytics.
     */
    public function userAnalytics(Request $request)
    {
        $period = $request->get('period', '30'); // days

        $analytics = [
            'summary' => [
                'total_users' => User::count(),
                'active_users' => User::whereHas('donations', function ($query) use ($period) {
                    $query->where('created_at', '>=', now()->subDays($period));
                })->count(),
                'new_users' => User::where('created_at', '>=', now()->subDays($period))->count(),
                'engagement_rate' => $this->getUserEngagementRate($period),
            ],
            'participation' => [
                'by_department' => $this->getUserParticipationByDepartment($period),
                'top_donors' => User::with(['donations' => function ($query) use ($period) {
                    $query->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($period));
                }])
                ->whereHas('donations', function ($query) use ($period) {
                    $query->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($period));
                })
                ->get()
                ->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'department' => $user->department,
                        'employee_id' => $user->employee_id,
                        'total_donated' => $user->donations->sum('amount'),
                        'donations_count' => $user->donations->count(),
                    ];
                })
                ->sortByDesc('total_donated')
                ->take(20)
                ->values(),
            ],
            'growth' => $this->getUserGrowthTrends($period),
        ];

        return response()->json($analytics);
    }

    /**
     * Calculate donation success rate.
     */
    private function calculateSuccessRate(): float
    {
        $total = Donation::count();
        if ($total === 0) return 0;
        
        $completed = Donation::where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get monthly donation trends.
     */
    private function getMonthlyDonationTrends(): array
    {
        return Donation::select(
                DB::raw('to_char(created_at, \'YYYY-MM\') as month'),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    /**
     * Get top categories by donation amount.
     */
    private function getTopCategories(): array
    {
        return DB::table('campaign_categories')
            ->join('campaigns', 'campaign_categories.id', '=', 'campaigns.category_id')
            ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->select(
                'campaign_categories.name',
                DB::raw('SUM(donations.amount) as total_raised'),
                DB::raw('COUNT(donations.id) as donations_count')
            )
            ->where('donations.status', 'completed')
            ->groupBy('campaign_categories.id', 'campaign_categories.name')
            ->orderBy('total_raised', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get top donors.
     */
    private function getTopDonors(): array
    {
        return User::select(
                'users.id',
                'users.name',
                'users.department',
                'users.employee_id',
                DB::raw('SUM(donations.amount) as total_donated'),
                DB::raw('COUNT(donations.id) as donations_count')
            )
            ->join('donations', 'users.id', '=', 'donations.user_id')
            ->where('donations.status', 'completed')
            ->where('donations.anonymous', false)
            ->groupBy('users.id', 'users.name', 'users.department', 'users.employee_id')
            ->orderBy('total_donated', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get campaign performance metrics.
     */
    private function getCampaignPerformance(): array
    {
        return Campaign::select(
                'id',
                'title',
                'target_amount',
                'current_amount',
                DB::raw('(current_amount / target_amount * 100) as progress_percentage'),
                'status'
            )
            ->where('status', '!=', 'draft')
            ->orderBy('progress_percentage', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    /**
     * Get donation trends for specified period.
     */
    private function getDonationTrends(int $period, string $groupBy): array
    {
        $dateFormat = match($groupBy) {
            'week' => 'IYYY-IW',
            'month' => 'YYYY-MM',
            default => 'YYYY-MM-DD'
        };

        return Donation::select(
                DB::raw("to_char(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get donations by payment method.
     */
    private function getDonationsByPaymentMethod(int $period): array
    {
        return Donation::select(
                'payment_method',
                DB::raw('COUNT(*) as count'),
                DB::raw('SUM(amount) as total')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('payment_method')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get donations by department.
     */
    private function getDonationsByDepartment(int $period): array
    {
        return User::select(
                'users.department',
                DB::raw('COUNT(donations.id) as count'),
                DB::raw('SUM(donations.amount) as total')
            )
            ->join('donations', 'users.id', '=', 'donations.user_id')
            ->where('donations.status', 'completed')
            ->where('donations.created_at', '>=', now()->subDays($period))
            ->groupBy('users.department')
            ->orderBy('total', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get hourly donation distribution.
     */
    private function getHourlyDonationDistribution(int $period): array
    {
        return Donation::select(
                DB::raw('extract(hour from created_at) as hour'),
                DB::raw('COUNT(*) as count')
            )
            ->where('status', 'completed')
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get()
            ->toArray();
    }

    /**
     * Get campaign success rate for period.
     */
    private function getCampaignSuccessRate(int $period): float
    {
        $total = Campaign::where('created_at', '>=', now()->subDays($period))->count();
        if ($total === 0) return 0;

        $successful = Campaign::where('created_at', '>=', now()->subDays($period))
            ->whereRaw('current_amount >= target_amount')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get campaigns by category for period.
     */
    private function getCampaignsByCategory(int $period): array
    {
        return DB::table('campaign_categories')
            ->join('campaigns', 'campaign_categories.id', '=', 'campaigns.category_id')
            ->select(
                'campaign_categories.name',
                DB::raw('COUNT(campaigns.id) as count'),
                DB::raw('AVG(campaigns.current_amount) as avg_raised'),
                DB::raw('AVG(campaigns.target_amount) as avg_target')
            )
            ->where('campaigns.created_at', '>=', now()->subDays($period))
            ->groupBy('campaign_categories.id', 'campaign_categories.name')
            ->orderBy('count', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get campaign completion rates.
     */
    private function getCampaignCompletionRates(int $period): array
    {
        return Campaign::select(
                DB::raw('
                    CASE 
                        WHEN current_amount >= target_amount THEN \'Completed\'
                        WHEN current_amount >= target_amount * 0.75 THEN \'75-100%\'
                        WHEN current_amount >= target_amount * 0.5 THEN \'50-75%\'
                        WHEN current_amount >= target_amount * 0.25 THEN \'25-50%\'
                        ELSE \'0-25%\'
                    END as completion_range
                '),
                DB::raw('COUNT(*) as count')
            )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('completion_range')
            ->get()
            ->toArray();
    }

    /**
     * Get user engagement rate.
     */
    private function getUserEngagementRate(int $period): float
    {
        $total = User::count();
        if ($total === 0) return 0;

        $active = User::whereHas('donations', function ($query) use ($period) {
            $query->where('created_at', '>=', now()->subDays($period));
        })->count();

        return round(($active / $total) * 100, 2);
    }

    /**
     * Get user participation by department.
     */
    private function getUserParticipationByDepartment(int $period): array
    {
        return User::select(
                'department',
                DB::raw('COUNT(DISTINCT users.id) as total_users'),
                DB::raw('COUNT(DISTINCT CASE WHEN donations.id IS NOT NULL THEN users.id END) as active_users'),
                DB::raw('COUNT(donations.id) as total_donations'),
                DB::raw('SUM(donations.amount) as total_donated')
            )
            ->leftJoin('donations', function ($join) use ($period) {
                $join->on('users.id', '=', 'donations.user_id')
                    ->where('donations.status', 'completed')
                    ->where('donations.created_at', '>=', now()->subDays($period));
            })
            ->groupBy('department')
            ->orderBy('total_donated', 'desc')
            ->get()
            ->toArray();
    }

    /**
     * Get user growth trends.
     */
    private function getUserGrowthTrends(int $period): array
    {
        return User::select(
                DB::raw('to_char(created_at, \'YYYY-MM-DD\') as date'),
                DB::raw('COUNT(*) as new_users')
            )
            ->where('created_at', '>=', now()->subDays($period))
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->toArray();
    }
}

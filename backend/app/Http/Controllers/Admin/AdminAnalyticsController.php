<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminAnalyticsController extends Controller
{
    /**
     * Get dashboard overview statistics.
     */
    public function dashboard(): \Illuminate\Http\JsonResponse
    {
        $stats = [
            'overview' => [
                'total_users' => User::count(),
                'active_users' => User::whereHas('donations', function ($query): void {
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
    public function donationAnalytics(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = (int) $request->get('period', '30'); // days
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
            'trends' => $this->getDonationTrends($period),
            'by_payment_method' => $this->getDonationsByPaymentMethod($period),
            'by_department' => $this->getDonationsByDepartment($period),
            'hourly_distribution' => $this->getHourlyDonationDistribution($period),
        ];

        return response()->json($analytics);
    }

    /**
     * Get detailed campaign analytics.
     */
    public function campaignAnalytics(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = (int) $request->get('period', '30'); // days

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
    public function userAnalytics(Request $request): \Illuminate\Http\JsonResponse
    {
        $period = (int) $request->get('period', '30'); // days

        $analytics = [
            'summary' => [
                'total_users' => User::count(),
                'active_users' => User::whereHas('donations', function ($query) use ($period): void {
                    $query->where('created_at', '>=', now()->subDays($period));
                })->count(),
                'new_users' => User::where('created_at', '>=', now()->subDays($period))->count(),
                'engagement_rate' => $this->getUserEngagementRate($period),
            ],
            'participation' => [
                'by_department' => $this->getUserParticipationByDepartment($period),
                'top_donors' => User::with(['donations' => function ($query) use ($period): void {
                    $query->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($period));
                }])
                ->whereHas('donations', function ($query) use ($period): void {
                    $query->where('status', 'completed')
                        ->where('created_at', '>=', now()->subDays($period));
                })
                ->get()
                ->map(function ($user): array {
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
        if ($total === 0) {
            return 0;
        }
        
        $completed = Donation::where('status', 'completed')->count();
        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get monthly donation trends.
     *
     * @return array<int, array{month: string, count: int, total: float}>
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
     *
     * @return array<int, array{name: string, total_raised: float, donations_count: int}>
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
     *
     * @return array<int, array{id: int, name: string, department: string, employee_id: string, total_donated: float, donations_count: int}>
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
     *
     * @return array<int, array{id: int, title: string, target_amount: float, current_amount: float, progress_percentage: float, status: string}>
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
     * Get donation trends over time.
     *
     * @return array<int, array{date: string, donation_count: int, total_amount: float}>
     */
    private function getDonationTrends(int $period): array
    {
        $startDate = now()->subDays($period);

        /** @var \Illuminate\Support\Collection<int, \stdClass> $dailyData */
        $dailyData = Donation::selectRaw('
            DATE(created_at) as date,
            COUNT(*) as donation_count,
            SUM(amount) as total_amount
        ')
        ->where('status', 'completed')
        ->where('created_at', '>=', $startDate)
        ->groupBy('date')
        ->orderBy('date')
        ->get();

        return $dailyData->map(function ($item): array {
            /** @var \stdClass $item */
            return [
                'date' => $item->date,
                'donation_count' => (int) $item->donation_count,
                'total_amount' => round((float) $item->total_amount, 2),
            ];
        })->toArray();
    }

    /**
     * Get donations breakdown by payment method.
     *
     * @return array<int, array{payment_method: string, count: int, total_amount: float}>
     */
    private function getDonationsByPaymentMethod(int $period): array
    {
        $startDate = now()->subDays($period);

        /** @var \Illuminate\Support\Collection<int, \stdClass> $result */
        $result = Donation::selectRaw('
            payment_method,
            COUNT(*) as count,
            SUM(amount) as total_amount
        ')
        ->where('status', 'completed')
        ->where('created_at', '>=', $startDate)
        ->groupBy('payment_method')
        ->orderBy('total_amount', 'desc')
        ->get();

        return $result->map(function ($item): array {
            /** @var \stdClass $item */
            return [
                'payment_method' => $item->payment_method,
                'count' => (int) $item->count,
                'total_amount' => round((float) $item->total_amount, 2),
            ];
        })->toArray();
    }

    /**
     * Get donations breakdown by department.
     *
     * @return array<int, array{department: string, count: int, total_amount: float}>
     */
    private function getDonationsByDepartment(int $period): array
    {
        $startDate = now()->subDays($period);

        /** @var \Illuminate\Support\Collection<int, \stdClass> $result */
        $result = User::selectRaw('
            department,
            COUNT(donations.id) as donation_count,
            SUM(donations.amount) as total_amount
        ')
        ->join('donations', 'users.id', '=', 'donations.user_id')
        ->where('donations.status', 'completed')
        ->where('donations.created_at', '>=', $startDate)
        ->groupBy('department')
        ->orderBy('total_amount', 'desc')
        ->get();

        return $result->map(function ($item): array {
            /** @var \stdClass $item */
            return [
                'department' => $item->department ?? 'Unknown',
                'count' => (int) $item->donation_count,
                'total_amount' => round((float) $item->total_amount, 2),
            ];
        })->toArray();
    }

    /**
     * Get hourly donation distribution.
     *
     * @return array<int, array{hour: int, donation_count: int, total_amount: float}>
     */
    private function getHourlyDonationDistribution(int $period): array
    {
        $startDate = now()->subDays($period);

        $hourlyData = Donation::selectRaw('
            EXTRACT(HOUR FROM created_at) as hour,
            COUNT(*) as donation_count,
            SUM(amount) as total_amount
        ')
        ->where('status', 'completed')
        ->where('created_at', '>=', $startDate)
        ->groupBy('hour')
        ->orderBy('hour')
        ->get();

        $distribution = array_fill(0, 24, [
            'hour' => 0,
            'donation_count' => 0,
            'total_amount' => 0.0,
        ]);

        foreach ($hourlyData as $item) {
            /** @var object{hour: string|int, donation_count: string|int, total_amount: string|float} $item */
            $hour = (int) $item->hour;
            $distribution[$hour] = [
                'hour' => $hour,
                'donation_count' => (int) $item->donation_count,
                'total_amount' => round((float) $item->total_amount, 2),
            ];
        }

        return array_values($distribution);
    }

    /**
     * Get campaign success rate for period.
     */
    private function getCampaignSuccessRate(int $period): float
    {
        $total = Campaign::where('created_at', '>=', now()->subDays($period))->count();
        if ($total === 0) {
            return 0;
        }

        $successful = Campaign::where('created_at', '>=', now()->subDays($period))
            ->whereRaw('current_amount >= target_amount')
            ->count();

        return round(($successful / $total) * 100, 2);
    }

    /**
     * Get campaigns breakdown by category.
     *
     * @return array<int, array{id: int, name: string, campaigns_count: int, total_target: float, total_raised: float, progress_percentage: float}>
     */
    private function getCampaignsByCategory(int $period): array
    {
        $startDate = now()->subDays($period);

        return CampaignCategory::withCount(['campaigns' => function ($query) use ($startDate): void {
            $query->where('created_at', '>=', $startDate);
        }])
        ->withSum(['campaigns as total_target' => function ($query) use ($startDate): void {
            $query->where('created_at', '>=', $startDate);
        }], 'target_amount')
        ->withSum(['campaigns as total_raised' => function ($query) use ($startDate): void {
            $query->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
                ->where('donations.status', 'completed')
                ->where('donations.created_at', '>=', $startDate);
        }], 'donations.amount')
        ->get()
        ->map(function ($category): array {
            /** @var \App\Models\CampaignCategory & object{campaigns_count: int|null, total_target: float|null, total_raised: float|null} $category */
            return [
                'id' => $category->id,
                'name' => $category->name,
                'campaigns_count' => (int) ($category->campaigns_count ?? 0),
                'total_target' => round((float) ($category->total_target ?? 0), 2),
                'total_raised' => round((float) ($category->total_raised ?? 0), 2),
                'progress_percentage' => ($category->total_target ?? 0) > 0 
                    ? round(($category->total_raised ?? 0) / ($category->total_target ?? 1) * 100, 2)
                    : 0,
            ];
        })->toArray();
    }

    /**
     * Get campaign completion rates.
     *
     * @return array{completion_rates: array<string, int>, total_campaigns: int, successful_campaigns: int, overall_success_rate: float}
     */
    private function getCampaignCompletionRates(int $period): array
    {
        $startDate = now()->subDays($period);

        $campaigns = Campaign::where('created_at', '>=', $startDate)
            ->withSum(['donations as total_raised' => function ($query) use ($startDate): void {
                $query->where('status', 'completed')
                    ->where('created_at', '>=', $startDate);
            }], 'amount')
            ->get();

        $totalCampaigns = $campaigns->count();
        if ($totalCampaigns === 0) {
            return [
                'completion_rates' => [],
                'total_campaigns' => 0,
                'successful_campaigns' => 0,
                'overall_success_rate' => 0,
            ];
        }

        $completionRanges = [
            '0-25%' => 0,
            '25-50%' => 0,
            '50-75%' => 0,
            '75-100%' => 0,
            '100%+' => 0,
        ];

        foreach ($campaigns as $campaign) {
            $progress = ($campaign->total_raised ?? 0) / max($campaign->target_amount, 1);

            if ($progress < 0.25) {
                $completionRanges['0-25%']++;
            } elseif ($progress < 0.50) {
                $completionRanges['25-50%']++;
            } elseif ($progress < 0.75) {
                $completionRanges['50-75%']++;
            } elseif ($progress < 1.0) {
                $completionRanges['75-100%']++;
            } else {
                $completionRanges['100%+']++;
            }
        }

        $successfulCampaigns = $completionRanges['100%+'];
        $overallSuccessRate = round(($successfulCampaigns / $totalCampaigns) * 100, 2);

        return [
            'completion_rates' => $completionRanges,
            'total_campaigns' => $totalCampaigns,
            'successful_campaigns' => $successfulCampaigns,
            'overall_success_rate' => $overallSuccessRate,
        ];
    }

    /**
     * Get user engagement rate.
     */
    private function getUserEngagementRate(int $period): float
    {
        $total = User::count();
        if ($total === 0) {
            return 0;
        }

        $active = User::whereHas('donations', function ($query) use ($period): void {
            $query->where('created_at', '>=', now()->subDays($period));
        })->count();

        return round(($active / $total) * 100, 2);
    }

    /**
     * Get user participation breakdown by department.
     *
     * @return array<int, array{department: string, total_users: int, active_users: int, participation_rate: float}>
     */
    private function getUserParticipationByDepartment(int $period): array
    {
        $startDate = now()->subDays($period);

        /** @var \Illuminate\Support\Collection<int, \stdClass> $result */
        $result = User::selectRaw('
            department,
            COUNT(DISTINCT users.id) as total_users,
            COUNT(DISTINCT CASE WHEN donations.id IS NOT NULL THEN users.id END) as active_users
        ')
        ->leftJoin('donations', function ($join) use ($startDate): void {
            $join->on('users.id', '=', 'donations.user_id')
                ->where('donations.status', 'completed')
                ->where('donations.created_at', '>=', $startDate);
        })
        ->groupBy('department')
        ->orderBy('active_users', 'desc')
        ->get();

        return $result->map(function ($item): array {
            /** @var \stdClass $item */
            $totalUsers = (int) $item->total_users;
            $activeUsers = (int) $item->active_users;
            $participationRate = $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 2) : 0;

            return [
                'department' => $item->department ?? 'Unknown',
                'total_users' => $totalUsers,
                'active_users' => $activeUsers,
                'participation_rate' => $participationRate,
            ];
        })->toArray();
    }

    /**
     * Get user growth trends.
     *
     * @return array<int, array{month: string, new_users: int}>
     */
    private function getUserGrowthTrends(int $period): array
    {
        $startDate = now()->subDays($period);

        /** @var \Illuminate\Support\Collection<int, \stdClass> $monthlyData */
        $monthlyData = User::selectRaw('
            DATE_TRUNC(\'month\', created_at) as month,
            COUNT(*) as new_users
        ')
        ->where('created_at', '>=', $startDate)
        ->groupBy('month')
        ->orderBy('month')
        ->get();

        return $monthlyData->map(function ($item): array {
            /** @var \stdClass $item */
            return [
                'month' => \Carbon\Carbon::parse($item->month)->format('Y-m'),
                'new_users' => (int) $item->new_users,
            ];
        })->toArray();
    }
}

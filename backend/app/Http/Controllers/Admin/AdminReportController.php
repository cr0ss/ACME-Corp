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

class AdminReportController extends Controller
{
    /**
     * Get comprehensive financial report.
     */
    public function financialReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'group_by' => 'sometimes|in:day,week,month,quarter',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $groupBy = $request->get('group_by', 'month');

        $report = [
            'summary' => [
                'total_raised' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount'),
                'total_donations' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->count(),
                'avg_donation' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->avg('amount'),
                'unique_donors' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->distinct('user_id')
                    ->count(),
                'campaigns_funded' => Campaign::whereHas('donations', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'completed')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                })->count(),
            ],
            'trends' => $this->getFinancialTrends($startDate, $endDate, $groupBy),
            'by_category' => $this->getFinancialByCategory($startDate, $endDate),
            'by_department' => $this->getFinancialByDepartment($startDate, $endDate),
            'by_payment_method' => $this->getFinancialByPaymentMethod($startDate, $endDate),
            'top_campaigns' => $this->getTopCampaignsByRaised($startDate, $endDate),
            'top_donors' => $this->getTopDonorsInPeriod($startDate, $endDate),
        ];

        // Log report generation
        AuditLog::createLog(
            $request->user()->id,
            'financial_report_generated',
            null,
            null,
            null,
            ['start_date' => $startDate, 'end_date' => $endDate, 'group_by' => $groupBy],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json($report);
    }

    /**
     * Get campaign performance report.
     */
    public function campaignReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'status' => 'sometimes|in:all,active,completed,draft,cancelled',
            'category_id' => 'sometimes|exists:campaign_categories,id',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $status = $request->get('status', 'all');
        $categoryId = $request->get('category_id');

        $query = Campaign::with(['category', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate]);

        if ($status !== 'all') {
            $query->where('status', $status);
        }

        if ($categoryId) {
            $query->where('category_id', $categoryId);
        }

        /** @var \Illuminate\Database\Eloquent\Collection<int, \App\Models\Campaign> $campaigns */
        $campaigns = $query->get();

        $report = [
            'summary' => [
                'total_campaigns' => $campaigns->count(),
                'total_target' => $campaigns->sum('target_amount'),
                'total_raised' => $campaigns->sum('current_amount'),
                'avg_target' => $campaigns->avg('target_amount'),
                'avg_raised' => $campaigns->avg('current_amount'),
                'success_rate' => $campaigns->where('current_amount', '>=', function($campaign) {
                    return $campaign->target_amount;
                })->count() / max($campaigns->count(), 1) * 100,
            ],
            'by_status' => $campaigns->groupBy('status')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_target' => $group->sum('target_amount'),
                    'total_raised' => $group->sum('current_amount'),
                ];
            }),
            'by_category' => $campaigns->groupBy('category.name')->map(function ($group) {
                return [
                    'count' => $group->count(),
                    'total_target' => $group->sum('target_amount'),
                    'total_raised' => $group->sum('current_amount'),
                    'avg_progress' => $group->avg(function ($campaign) {
                        return ($campaign->current_amount / max($campaign->target_amount, 1)) * 100;
                    }),
                ];
            }),
            'performance_ranges' => [
                '0-25%' => $campaigns->filter(function ($c) {
                    return ($c->current_amount / max($c->target_amount, 1)) < 0.25;
                })->count(),
                '25-50%' => $campaigns->filter(function ($c) {
                    $progress = $c->current_amount / max($c->target_amount, 1);
                    return $progress >= 0.25 && $progress < 0.50;
                })->count(),
                '50-75%' => $campaigns->filter(function ($c) {
                    $progress = $c->current_amount / max($c->target_amount, 1);
                    return $progress >= 0.50 && $progress < 0.75;
                })->count(),
                '75-100%' => $campaigns->filter(function ($c) {
                    $progress = $c->current_amount / max($c->target_amount, 1);
                    return $progress >= 0.75 && $progress < 1.0;
                })->count(),
                'Over 100%' => $campaigns->filter(function ($c) {
                    return ($c->current_amount / max($c->target_amount, 1)) >= 1.0;
                })->count(),
            ],
            'detailed_campaigns' => $campaigns->map(function (\App\Models\Campaign $campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'category' => $campaign->category->name,
                    'creator' => $campaign->user->name,
                    'target_amount' => $campaign->target_amount,
                    'current_amount' => $campaign->current_amount,
                    'progress_percentage' => ($campaign->current_amount / max($campaign->target_amount, 1)) * 100,
                    'status' => $campaign->status,
                    'donations_count' => $campaign->donations()->where('status', 'completed')->count(),
                    'created_at' => $campaign->created_at->format('Y-m-d'),
                    'days_active' => $campaign->created_at->diffInDays(min($campaign->end_date, now())),
                ];
            }),
        ];

        return response()->json($report);
    }

    /**
     * Get user engagement report.
     */
    public function userEngagementReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'department' => 'sometimes|string',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;
        $department = $request->get('department');

        $query = User::with(['donations' => function ($q) use ($startDate, $endDate) {
            $q->where('status', 'completed')
              ->whereBetween('created_at', [$startDate, $endDate]);
        }]);

        if ($department) {
            $query->where('department', $department);
        }

        $users = $query->get();

        $report = [
            'summary' => [
                'total_users' => $users->count(),
                'active_users' => $users->filter(function ($user) {
                    return $user->donations->count() > 0;
                })->count(),
                'engagement_rate' => ($users->filter(function ($user) {
                    return $user->donations->count() > 0;
                })->count() / max($users->count(), 1)) * 100,
                'avg_donations_per_user' => $users->avg(function ($user) {
                    return $user->donations->count();
                }),
                'avg_amount_per_user' => $users->avg(function ($user) {
                    return $user->donations->sum('amount');
                }),
            ],
            'by_department' => $users->groupBy('department')->map(function ($group) {
                return [
                    'total_users' => $group->count(),
                    'active_users' => $group->filter(function ($user) {
                        return $user->donations->count() > 0;
                    })->count(),
                    'total_donations' => $group->sum(function ($user) {
                        return $user->donations->count();
                    }),
                    'total_amount' => $group->sum(function ($user) {
                        return $user->donations->sum('amount');
                    }),
                    'engagement_rate' => ($group->filter(function ($user) {
                        return $user->donations->count() > 0;
                    })->count() / max($group->count(), 1)) * 100,
                ];
            }),
            'participation_levels' => [
                'non_participants' => $users->filter(function ($user) {
                    return $user->donations->count() === 0;
                })->count(),
                'light_participants' => $users->filter(function ($user) {
                    return $user->donations->count() >= 1 && $user->donations->count() <= 3;
                })->count(),
                'moderate_participants' => $users->filter(function ($user) {
                    return $user->donations->count() >= 4 && $user->donations->count() <= 10;
                })->count(),
                'heavy_participants' => $users->filter(function ($user) {
                    return $user->donations->count() > 10;
                })->count(),
            ],
            'top_participants' => $users->sortByDesc(function ($user) {
                return $user->donations->sum('amount');
            })->take(20)->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'employee_id' => $user->employee_id,
                    'department' => $user->department,
                    'donations_count' => $user->donations->count(),
                    'total_donated' => $user->donations->sum('amount'),
                    'avg_donation' => $user->donations->avg('amount') ?? 0,
                ];
            })->values(),
        ];

        return response()->json($report);
    }

    /**
     * Get comprehensive CSR impact report.
     */
    public function impactReport(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = $request->start_date;
        $endDate = $request->end_date;

        $report = [
            'overview' => [
                'total_funds_raised' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->sum('amount'),
                'campaigns_completed' => Campaign::where('status', 'completed')
                    ->whereBetween('end_date', [$startDate, $endDate])
                    ->count(),
                'employees_participated' => Donation::where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate])
                    ->distinct('user_id')
                    ->count(),
                'beneficiary_categories' => CampaignCategory::whereHas('campaigns.donations', function ($query) use ($startDate, $endDate) {
                    $query->where('status', 'completed')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                })->count(),
            ],
            'category_impact' => CampaignCategory::withSum(['campaigns as total_raised' => function ($query) use ($startDate, $endDate) {
                $query->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
                    ->where('donations.status', 'completed')
                    ->whereBetween('donations.created_at', [$startDate, $endDate]);
            }], 'donations.amount')
            ->withCount(['campaigns as campaigns_count' => function ($query) use ($startDate, $endDate) {
                $query->whereHas('donations', function ($subQuery) use ($startDate, $endDate) {
                    $subQuery->where('status', 'completed')
                        ->whereBetween('created_at', [$startDate, $endDate]);
                });
            }])
            ->get()
            ->filter(function ($category) {
                return ($category->total_raised ?? 0) > 0;
            })
            ->sortByDesc('total_raised')
            ->map(function ($category) {
                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'description' => $category->description,
                    'total_raised' => $category->total_raised ?? 0,
                    'campaigns_count' => $category->campaigns_count ?? 0,
                ];
            })
            ->values(),
            'monthly_impact' => $this->getMonthlyImpactTrends($startDate, $endDate),
            'success_stories' => Campaign::with(['category', 'user'])
                ->whereRaw('current_amount >= target_amount')
                ->whereBetween('end_date', [$startDate, $endDate])
                ->orderBy('current_amount', 'desc')
                ->limit(10)
                ->get()
                ->map(function (\App\Models\Campaign $campaign) {
                    return [
                        'id' => $campaign->id,
                        'title' => $campaign->title,
                        'category' => $campaign->category->name,
                        'target_amount' => $campaign->target_amount,
                        'final_amount' => $campaign->current_amount,
                        'percentage_achieved' => ($campaign->current_amount / $campaign->target_amount) * 100,
                        'donors_count' => $campaign->donations()->where('status', 'completed')->count(),
                        'creator' => $campaign->user->name,
                    ];
                }),
            'department_participation' => $this->getDepartmentParticipation($startDate, $endDate),
        ];

        return response()->json($report);
    }

    /**
     * Export comprehensive data.
     */
    public function exportData(Request $request)
    {
        $request->validate([
            'type' => 'required|in:donations,campaigns,users,financial,impact',
            'format' => 'required|in:csv,json,excel',
            'start_date' => 'sometimes|date',
            'end_date' => 'sometimes|date|after_or_equal:start_date',
        ]);

        $type = $request->type;
        $format = $request->format;
        $startDate = $request->get('start_date', now()->subYear());
        $endDate = $request->get('end_date', now());

        $data = match($type) {
            'donations' => $this->exportDonationsData($startDate, $endDate),
            'campaigns' => $this->exportCampaignsData($startDate, $endDate),
            'users' => $this->exportUsersData($startDate, $endDate),
            'financial' => $this->exportFinancialData($startDate, $endDate),
            'impact' => $this->exportImpactData($startDate, $endDate),
            default => throw new \InvalidArgumentException("Invalid export type: {$type}"),
        };

        // Log the export
        AuditLog::createLog(
            $request->user()->id,
            "export_{$type}_data",
            null,
            null,
            null,
            [
                'type' => $type,
                'format' => $format,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'records_count' => count($data)
            ],
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'data' => $data,
            'type' => $type,
            'format' => $format,
            'filename' => "{$type}_export_" . now()->format('Y-m-d_H-i-s'),
            'count' => count($data),
            'generated_at' => now()->toISOString(),
        ]);
    }

    // Private helper methods for data processing

    private function getFinancialTrends(string $startDate, string $endDate, string $groupBy): array
    {
        $dateFormat = match($groupBy) {
            'day' => 'YYYY-MM-DD',
            'week' => 'IYYY-IW',
            'quarter' => 'YYYY-Q',
            default => 'YYYY-MM'
        };

        return Donation::select(
                DB::raw("to_char(created_at, '{$dateFormat}') as period"),
                DB::raw('COUNT(*) as donations_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    private function getFinancialByCategory(string $startDate, string $endDate): array
    {
        return DB::table('campaign_categories')
            ->join('campaigns', 'campaign_categories.id', '=', 'campaigns.category_id')
            ->join('donations', 'campaigns.id', '=', 'donations.campaign_id')
            ->select(
                'campaign_categories.name',
                DB::raw('COUNT(donations.id) as donations_count'),
                DB::raw('SUM(donations.amount) as total_amount'),
                DB::raw('AVG(donations.amount) as avg_donation')
            )
            ->where('donations.status', 'completed')
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('campaign_categories.id', 'campaign_categories.name')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }

    private function getFinancialByDepartment(string $startDate, string $endDate): array
    {
        return DB::table('users')
            ->join('donations', 'users.id', '=', 'donations.user_id')
            ->select(
                'users.department',
                DB::raw('COUNT(donations.id) as donations_count'),
                DB::raw('SUM(donations.amount) as total_amount'),
                DB::raw('COUNT(DISTINCT users.id) as unique_donors')
            )
            ->where('donations.status', 'completed')
            ->whereBetween('donations.created_at', [$startDate, $endDate])
            ->groupBy('users.department')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }

    private function getFinancialByPaymentMethod(string $startDate, string $endDate): array
    {
        return Donation::select(
                'payment_method',
                DB::raw('COUNT(*) as donations_count'),
                DB::raw('SUM(amount) as total_amount'),
                DB::raw('AVG(amount) as avg_amount')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('payment_method')
            ->orderBy('total_amount', 'desc')
            ->get()
            ->toArray();
    }

    private function getTopCampaignsByRaised(string $startDate, string $endDate): array
    {
        return Campaign::with(['category', 'user'])
            ->whereHas('donations', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            })
            ->withSum(['donations as total_raised' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }], 'amount')
            ->orderBy('total_raised', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getTopDonorsInPeriod(string $startDate, string $endDate): array
    {
        return User::with(['donations' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->whereHas('donations', function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
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
            ->take(10)
            ->values()
            ->toArray();
    }

    private function getMonthlyImpactTrends(string $startDate, string $endDate): array
    {
        return Donation::select(
                DB::raw('to_char(created_at, \'YYYY-MM\') as month'),
                DB::raw('COUNT(*) as donations_count'),
                DB::raw('SUM(amount) as total_raised'),
                DB::raw('COUNT(DISTINCT user_id) as unique_donors'),
                DB::raw('COUNT(DISTINCT campaign_id) as campaigns_supported')
            )
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('month')
            ->orderBy('month')
            ->get()
            ->toArray();
    }

    private function getDepartmentParticipation(string $startDate, string $endDate): array
    {
        return User::select(
                'department',
                DB::raw('COUNT(DISTINCT users.id) as total_employees'),
                DB::raw('COUNT(DISTINCT CASE WHEN donations.id IS NOT NULL THEN users.id END) as participants'),
                DB::raw('COUNT(donations.id) as total_donations'),
                DB::raw('SUM(donations.amount) as total_contributed')
            )
            ->leftJoin('donations', function ($join) use ($startDate, $endDate) {
                $join->on('users.id', '=', 'donations.user_id')
                    ->where('donations.status', 'completed')
                    ->whereBetween('donations.created_at', [$startDate, $endDate]);
            })
            ->groupBy('department')
            ->orderBy('total_contributed', 'desc')
            ->get()
            ->toArray();
    }

    private function exportDonationsData(string $startDate, string $endDate): array
    {
        return Donation::with(['user', 'campaign.category'])
            ->where('status', 'completed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function (\App\Models\Donation $donation) {
                return [
                    'id' => $donation->id,
                    'amount' => $donation->amount,
                    'donor_name' => $donation->anonymous ? 'Anonymous' : $donation->user->name,
                    'donor_employee_id' => $donation->anonymous ? 'Anonymous' : $donation->user->employee_id,
                    'donor_department' => $donation->anonymous ? 'Anonymous' : $donation->user->department,
                    'campaign_title' => $donation->campaign->title,
                    'category' => $donation->campaign->category->name,
                    'payment_method' => $donation->payment_method,
                    'transaction_id' => $donation->transaction_id,
                    'anonymous' => $donation->anonymous ? 'Yes' : 'No',
                    'message' => $donation->message,
                    'donated_at' => $donation->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    private function exportCampaignsData(string $startDate, string $endDate): array
    {
        return Campaign::with(['category', 'user'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->get()
            ->map(function (\App\Models\Campaign $campaign) {
                return [
                    'id' => $campaign->id,
                    'title' => $campaign->title,
                    'description' => strip_tags($campaign->description),
                    'category' => $campaign->category->name,
                    'creator_name' => $campaign->user->name,
                    'creator_department' => $campaign->user->department,
                    'target_amount' => $campaign->target_amount,
                    'current_amount' => $campaign->current_amount,
                    'progress_percentage' => ($campaign->current_amount / max($campaign->target_amount, 1)) * 100,
                    'status' => $campaign->status,
                    'featured' => $campaign->featured ? 'Yes' : 'No',
                    'start_date' => $campaign->start_date ? \Carbon\Carbon::parse($campaign->start_date)->format('Y-m-d') : null,
                    'end_date' => $campaign->end_date ? \Carbon\Carbon::parse($campaign->end_date)->format('Y-m-d') : null,
                    'created_at' => $campaign->created_at->format('Y-m-d H:i:s'),
                    'donations_count' => $campaign->donations()->where('status', 'completed')->count(),
                ];
            })
            ->toArray();
    }

    private function exportUsersData(string $startDate, string $endDate): array
    {
        return User::with(['donations' => function ($query) use ($startDate, $endDate) {
                $query->where('status', 'completed')
                    ->whereBetween('created_at', [$startDate, $endDate]);
            }])
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'employee_id' => $user->employee_id,
                    'department' => $user->department,
                    'role' => $user->role,
                    'is_admin' => $user->is_admin ? 'Yes' : 'No',
                    'donations_in_period' => $user->donations->count(),
                    'total_donated_in_period' => $user->donations->sum('amount'),
                    'avg_donation_in_period' => $user->donations->avg('amount') ?? 0,
                    'joined_at' => $user->created_at->format('Y-m-d H:i:s'),
                ];
            })
            ->toArray();
    }

    private function exportFinancialData(string $startDate, string $endDate): array
    {
        // This would return detailed financial breakdown
        return $this->getFinancialTrends($startDate, $endDate, 'month');
    }

    private function exportImpactData(string $startDate, string $endDate): array
    {
        // This would return impact metrics
        return $this->getMonthlyImpactTrends($startDate, $endDate);
    }
}

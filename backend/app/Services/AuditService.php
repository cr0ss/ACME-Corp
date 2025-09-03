<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class AuditService
{
    /**
     * Get audit logs with filtering options.
     *
     * @param array<string, mixed> $filters
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator<int, \App\Models\AuditLog>
     */
    public function getAuditLogs(array $filters = [], int $perPage = 50): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        $query = AuditLog::with(['user'])
            ->orderBy('created_at', 'desc');

        // Apply filters
        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        if (isset($filters['model_type'])) {
            $query->where('model_type', $filters['model_type']);
        }

        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['ip_address'])) {
            $query->where('ip_address', $filters['ip_address']);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get user activity summary.
     *
     * @return array<string, mixed>
     */
    public function getUserActivitySummary(User $user, ?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $logs = AuditLog::where('user_id', $user->id)
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalActions = $logs->count();
        $actionBreakdown = $logs->select('action', DB::raw('COUNT(*) as count'))
            ->groupBy('action')
            ->pluck('count', 'action')
            ->toArray();

        $dailyActivity = $logs->select(
                DB::raw('DATE(created_at) as date'),
                DB::raw('COUNT(*) as actions')
            )
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->pluck('actions', 'date')
            ->toArray();

        return [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'department' => $user->department,
            ],
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_actions' => $totalActions,
                'actions_per_day' => $totalActions > 0 ? round($totalActions / $startDate->diffInDays($endDate, false), 2) : 0,
                'most_common_action' => collect($actionBreakdown)->sortDesc()->keys()->first(),
            ],
            'action_breakdown' => $actionBreakdown,
            'daily_activity' => $dailyActivity,
        ];
    }

    /**
     * Get system-wide audit summary.
     *
     * @return array<string, mixed>
     */
    public function getSystemAuditSummary(?string $startDate = null, ?string $endDate = null): array
    {
        $startDate = $startDate ? \Carbon\Carbon::parse($startDate) : now()->subMonth();
        $endDate = $endDate ? \Carbon\Carbon::parse($endDate) : now();

        $logs = AuditLog::whereBetween('created_at', [$startDate, $endDate]);

        $totalActions = $logs->count();
        $uniqueUsers = $logs->distinct('user_id')->count();
        $uniqueIPs = $logs->whereNotNull('ip_address')->distinct('ip_address')->count();

        return [
            'period' => [
                'start_date' => $startDate->toDateString(),
                'end_date' => $endDate->toDateString(),
            ],
            'summary' => [
                'total_actions' => $totalActions,
                'unique_users' => $uniqueUsers,
                'unique_ip_addresses' => $uniqueIPs,
                'actions_per_day' => $totalActions > 0 ? round($totalActions / $startDate->diffInDays($endDate, false), 2) : 0,
            ],
            'action_breakdown' => $this->getActionBreakdown($startDate, $endDate),
            'model_breakdown' => $this->getModelBreakdown($startDate, $endDate),
            'top_users' => $this->getTopUsers($startDate, $endDate),
            'suspicious_activities' => $this->getSuspiciousActivities($startDate, $endDate),
        ];
    }

    /**
     * Get audit logs for a specific model.
     *
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    /**
     * @return \Illuminate\Support\Collection<int, array{id: int, action: string, user: array{id: int, name: string, email: string}|null, old_values: array<string, mixed>|null, new_values: array<string, mixed>|null, ip_address: string|null, created_at: \Illuminate\Support\Carbon}>
     */
    public function getModelAuditLogs(string $modelType, int $modelId): Collection
    {
        return AuditLog::with(['user'])
            ->where('model_type', $modelType)
            ->where('model_id', $modelId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log): array {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'user' => $log->user ? [
                        'id' => $log->user->id,
                        'name' => $log->user->name,
                        'email' => $log->user->email,
                    ] : null,
                    'old_values' => $log->old_values,
                    'new_values' => $log->new_values,
                    'ip_address' => $log->ip_address,
                    'created_at' => $log->created_at,
                ];
            });
    }

    /**
     * Create an audit log entry.
     *
     * @param array<string, mixed>|null $oldValues
     * @param array<string, mixed>|null $newValues
     */
    public function createLog(
        ?int $userId,
        string $action,
        ?string $modelType = null,
        ?int $modelId = null,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): AuditLog {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => $ipAddress ?? request()->ip(),
            'user_agent' => $userAgent ?? request()->userAgent(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Clean up old audit logs.
     */
    public function cleanupOldLogs(int $keepDays = 365): int
    {
        $cutoffDate = now()->subDays($keepDays);
        
        return AuditLog::where('created_at', '<', $cutoffDate)->delete();
    }

    /**
     * Export audit logs.
     *
     * @param array<string, mixed> $filters
     * @return \Illuminate\Support\Collection<int, array<string, mixed>>
     */
    public function exportAuditLogs(array $filters = []): Collection
    {
        $query = AuditLog::with(['user']);

        // Apply filters
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }

        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }

        if (isset($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        if (isset($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        /** @var \Illuminate\Support\Collection<int, array<string, mixed>> $result */
        $result = $query->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($log): array {
                return [
                    'id' => $log->id,
                    'timestamp' => $log->created_at->toISOString(),
                    'user_name' => $log->user?->name,
                    'user_email' => $log->user?->email,
                    'action' => $log->action,
                    'model_type' => $log->model_type,
                    'model_id' => $log->model_id,
                    'ip_address' => $log->ip_address,
                    'user_agent' => $log->user_agent,
                    'old_values' => $log->old_values ? json_encode($log->old_values) : null,
                    'new_values' => $log->new_values ? json_encode($log->new_values) : null,
                ];
            });

        return $result;
    }

    /**
     * Get action breakdown.
     *
     * @return array<string, int>
     */
    private function getActionBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return AuditLog::select('action', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('action')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'action')
            ->toArray();
    }

    /**
     * Get model breakdown.
     *
     * @return array<string, int>
     */
    private function getModelBreakdown(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return AuditLog::select('model_type', DB::raw('COUNT(*) as count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('model_type')
            ->groupBy('model_type')
            ->orderBy('count', 'desc')
            ->get()
            ->pluck('count', 'model_type')
            ->toArray();
    }

    /**
     * Get top users by activity.
     *
     * @return \Illuminate\Support\Collection<int, array{user: array{id: int, name: string, email: string, department: string|null}|null, action_count: int}>
     */
    private function getTopUsers(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, int $limit = 10): Collection
    {
        return AuditLog::with(['user'])
            ->select('user_id', DB::raw('COUNT(*) as action_count'))
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->groupBy('user_id')
            ->orderBy('action_count', 'desc')
            ->limit($limit)
            ->get()
            ->map(function ($item): array {
                return [
                    'user' => $item->user ? [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'email' => $item->user->email,
                        'department' => $item->user->department,
                    ] : null,
                    'action_count' => (int) $item->action_count,
                ];
            });
    }

    /**
     * Get suspicious activities.
     *
     * @return \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array<string, mixed>>>
     */
    private function getSuspiciousActivities(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): Collection
    {
        // Multiple logins from different IPs
        $multipleIPs = AuditLog::select('user_id', DB::raw('COUNT(DISTINCT ip_address) as ip_count'))
            ->where('action', 'user_login')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('user_id')
            ->whereNotNull('ip_address')
            ->groupBy('user_id')
            ->having('ip_count', '>', 3)
            ->with(['user'])
            ->get();

        // Failed login attempts
        $failedLogins = AuditLog::select('ip_address', DB::raw('COUNT(*) as failed_count'))
            ->where('action', 'login_failed')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereNotNull('ip_address')
            ->groupBy('ip_address')
            ->having('failed_count', '>', 5)
            ->get();

        /** @var array{multiple_ip_logins: \Illuminate\Support\Collection<int, array{user: array{id: int, name: string, email: string}|null, unique_ip_count: int}>, excessive_failed_logins: \Illuminate\Support\Collection<int, array{ip_address: string, failed_attempts: int}>} $suspiciousActivities */
        $suspiciousActivities = [
            'multiple_ip_logins' => $multipleIPs->map(function ($item): array {
                return [
                    'user' => $item->user ? [
                        'id' => $item->user->id,
                        'name' => $item->user->name,
                        'email' => $item->user->email,
                    ] : null,
                    'unique_ip_count' => $item->ip_count,
                ];
            }),
            'excessive_failed_logins' => $failedLogins->map(function ($item): array {
                return [
                    'ip_address' => $item->ip_address,
                    'failed_attempts' => $item->failed_count,
                ];
            }),
        ];

        /** @var \Illuminate\Support\Collection<string, \Illuminate\Support\Collection<int, array<string, mixed>>> $result */
        $result = collect($suspiciousActivities);
        return $result;
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminUserController extends Controller
{
    /**
     * Display a listing of users with advanced filtering.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate pagination and filtering parameters
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'search' => 'string|max:255',
            'department' => 'string|max:100',
            'role' => 'string|max:100',
            'is_admin' => 'boolean',
            'has_donations' => 'boolean',
            'created_from' => 'date',
            'created_to' => 'date',
            'sort_by' => 'string|in:name,email,department,role,created_at',
            'sort_order' => 'string|in:asc,desc',
        ]);

        $query = User::query();

        // Search functionality
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%")
                  ->orWhere('department', 'like', "%{$search}%");
            });
        }

        // Filter by department
        if ($request->has('department') && $request->department !== '') {
            $query->where('department', $request->department);
        }

        // Filter by role
        if ($request->has('role') && $request->role !== '') {
            $query->where('role', $request->role);
        }

        // Filter by admin status
        if ($request->has('is_admin') && $request->is_admin !== '') {
            $query->where('is_admin', $request->boolean('is_admin'));
        }

        // Filter by activity (users with donations)
        if ($request->has('has_donations') && $request->has_donations !== '') {
            if ($request->boolean('has_donations')) {
                $query->whereHas('donations');
            } else {
                $query->whereDoesntHave('donations');
            }
        }

        // Date range filter
        if ($request->has('created_from')) {
            $query->where('created_at', '>=', $request->created_from);
        }
        if ($request->has('created_to')) {
            $query->where('created_at', '<=', $request->created_to);
        }

        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        
        $allowedSortColumns = ['name', 'email', 'department', 'role', 'created_at'];
        if (in_array($sortBy, $allowedSortColumns)) {
            $query->orderBy($sortBy, $sortOrder);
        }

        // Include related data
        $query->withCount(['donations', 'campaigns']);
        $query->with(['donations' => function ($q): void {
            $q->where('status', 'completed')->select('user_id', 'amount');
        }]);

        $users = $query->paginate($request->get('per_page', 15));

        // Add computed fields
        $users->getCollection()->transform(function (\App\Models\User $user): \App\Models\User {
            // Add dynamic properties that PHPStan can understand
            $user->setAttribute('total_donated', $user->donations->sum('amount'));
            $user->setAttribute('donation_count', $user->donations_count ?? 0);
            $user->setAttribute('campaign_count', $user->campaigns_count ?? 0);
            unset($user->donations); // Remove the donations collection to clean up response
            return $user;
        });

        return response()->json($users);
    }

    /**
     * Store a newly created user.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'employee_id' => 'required|string|max:50|unique:users',
            'department' => 'required|string|max:100',
            'role' => 'required|string|max:100',
            'password' => 'required|string|min:8|confirmed',
            'is_admin' => 'boolean',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'employee_id' => $request->employee_id,
            'department' => $request->department,
            'role' => $request->role,
            'password' => Hash::make($request->password),
            'is_admin' => $request->boolean('is_admin', false),
            'email_verified_at' => now(), // Auto-verify admin created users
        ]);

        // Log the user creation
        $adminUser = $request->user();
        if ($adminUser) {
            AuditLog::createLog(
                $adminUser->id,
                'user_created',
                'App\Models\User',
                $user->id,
                null,
                $user->toArray(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return response()->json($user, 201);
    }

    /**
     * Display the specified user.
     */
    public function show(User $user): \Illuminate\Http\JsonResponse
    {
        $user->load([
            'donations' => function ($query): void {
                $query->with(['campaign:id,title'])
                    ->orderBy('created_at', 'desc')
                    ->limit(10);
            },
            'campaigns' => function ($query): void {
                $query->with(['category:id,name'])
                    ->orderBy('created_at', 'desc')
                    ->limit(5);
            }
        ]);

        $user->setAttribute('total_donated', $user->donations->where('status', 'completed')->sum('amount'));
        $user->setAttribute('recent_activity', AuditLog::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get());

        return response()->json($user);
    }

    /**
     * Update the specified user.
     */
    public function update(Request $request, User $user): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'employee_id' => [
                'sometimes',
                'required',
                'string',
                'max:50',
                Rule::unique('users')->ignore($user->id),
            ],
            'department' => 'sometimes|required|string|max:100',
            'role' => 'sometimes|required|string|max:100',
            'is_admin' => 'sometimes|boolean',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $oldValues = $user->toArray();
        
        $updateData = $request->only([
            'name', 'email', 'employee_id', 'department', 'role', 'is_admin'
        ]);

        if ($request->has('password')) {
            $updateData['password'] = Hash::make($request->password);
        }

        $user->update($updateData);

        // Log the user update
        $adminUser = $request->user();
        if ($adminUser) {
            AuditLog::createLog(
                $adminUser->id,
                'user_updated',
                'App\Models\User',
                $user->id,
                $oldValues,
                $user->toArray(),
                $request->ip(),
                $request->userAgent()
            );
        }

        return response()->json($user);
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(Request $request, User $user): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        // Prevent deletion of the last admin
        if ($user->is_admin && User::where('is_admin', true)->count() <= 1) {
            return response()->json([
                'message' => 'Cannot delete the last admin user'
            ], 422);
        }

        // Prevent self-deletion
        $adminUser = $request->user();
        if ($adminUser && $user->id === $adminUser->id) {
            return response()->json([
                'message' => 'Cannot delete your own account'
            ], 422);
        }

        // Log the user deletion before deleting
        $adminUser = $request->user();
        if ($adminUser) {
            AuditLog::createLog(
                $adminUser->id,
                'user_deleted',
                'App\Models\User',
                $user->id,
                $user->toArray(),
                null,
                $request->ip(),
                $request->userAgent()
            );
        }

        $user->delete();

        return response()->noContent();
    }

    /**
     * Get user statistics and analytics.
     */
    public function statistics(): \Illuminate\Http\JsonResponse
    {
        $stats = [
            'overview' => [
                'total_users' => User::count(),
                'admin_users' => User::where('is_admin', true)->count(),
                'active_users' => User::whereHas('donations', function ($query): void {
                    $query->where('created_at', '>=', now()->subDays(30));
                })->count(),
                'new_users_this_month' => User::where('created_at', '>=', now()->startOfMonth())->count(),
            ],
            'departments' => User::selectRaw('department, COUNT(*) as count')
                ->groupBy('department')
                ->orderBy('count', 'desc')
                ->get(),
            'roles' => User::selectRaw('role, COUNT(*) as count')
                ->groupBy('role')
                ->orderBy('count', 'desc')
                ->get(),
            'registration_trend' => User::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(30))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];

        return response()->json($stats);
    }

    /**
     * Bulk update users.
     */
    public function bulkUpdate(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'user_ids' => 'required|array',
            'user_ids.*' => 'exists:users,id',
            'action' => 'required|in:activate,deactivate,change_department,change_role,make_admin,remove_admin',
            'value' => 'sometimes|string|max:255',
        ]);

        $userIds = $request->user_ids;
        $action = $request->action;
        $value = $request->value;

        $users = User::whereIn('id', $userIds)->get();
        $updated = [];

        foreach ($users as $user) {
            $oldValues = $user->toArray();
            
            switch ($action) {
                case 'change_department':
                    $user->department = $value;
                    break;
                case 'change_role':
                    $user->role = $value;
                    break;
                case 'make_admin':
                    $user->is_admin = true;
                    break;
                case 'remove_admin':
                    // Prevent removal of admin status from the last admin
                    if (User::where('is_admin', true)->count() > 1) {
                        $user->is_admin = false;
                    }
                    break;
            }

            if ($user->isDirty()) {
                $user->save();
                $updated[] = $user->id;

                // Log the bulk update
                $adminUser = $request->user();
                if ($adminUser) {
                    AuditLog::createLog(
                        $adminUser->id,
                        "bulk_update_{$action}",
                        'App\Models\User',
                        $user->id,
                        $oldValues,
                        $user->toArray(),
                        $request->ip(),
                        $request->userAgent()
                    );
                }
            }
        }

        return response()->json([
            'message' => 'Bulk update completed',
            'updated_users' => $updated,
            'total_updated' => count($updated),
        ]);
    }

    /**
     * Export users data.
     */
    public function export(Request $request): \Illuminate\Http\JsonResponse
    {
        $format = $request->get('format', 'csv'); // csv, json, xlsx
        
        $query = User::with(['donations' => function ($q): void {
            $q->where('status', 'completed');
        }]);

        // Apply same filters as index method
        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        if ($request->has('department') && $request->department !== '') {
            $query->where('department', $request->department);
        }

        $users = $query->get()->map(function ($user): array {
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'employee_id' => $user->employee_id,
                'department' => $user->department,
                'role' => $user->role,
                'is_admin' => $user->is_admin ? 'Yes' : 'No',
                'total_donated' => $user->donations->sum('amount'),
                'donations_count' => $user->donations->count(),
                'created_at' => $user->created_at?->format('Y-m-d H:i:s') ?? 'Unknown',
            ];
        });

        // Log the export
        $adminUser = $request->user();
        if ($adminUser) {
            AuditLog::createLog(
                $adminUser->id,
                'users_exported',
                null,
                null,
                null,
                ['format' => $format, 'count' => $users->count()],
                $request->ip(),
                $request->userAgent()
            );
        }

        return response()->json([
            'data' => $users,
            'format' => $format,
            'filename' => 'users_export_' . now()->format('Y-m-d_H-i-s'),
            'count' => $users->count(),
        ]);
    }
}

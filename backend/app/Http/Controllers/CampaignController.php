<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\CampaignCategory;
use App\Models\Donation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CampaignController extends Controller
{
    /**
     * Get all campaigns with filtering and pagination.
     * 
     * Retrieves a paginated list of campaigns with optional filtering by status, category, 
     * search query, and other parameters. Excludes draft campaigns by default.
     * 
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        // Validate pagination and filtering parameters
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'category_id' => 'integer|exists:campaign_categories,id',
            'status' => 'string|in:active,completed,cancelled',
            'search' => 'string|max:255',
            'featured' => 'string|in:true,false',
            'sort_by' => 'string|in:created_at,title,target_amount,current_amount,end_date',
            'sort_order' => 'string|in:asc,desc',
        ]);

        $query = Campaign::with(['category', 'user'])
            ->where('status', '!=', 'draft');

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    /**
     * Display a listing of all campaigns for admin.
     */
    public function adminIndex(Request $request)
    {
        // Validate pagination and filtering parameters
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'category_id' => 'integer|exists:campaign_categories,id',
            'status' => 'string|in:active,completed,cancelled,draft',
            'search' => 'string|max:255',
            'featured' => 'string|in:true,false',
            'sort_by' => 'string|in:created_at,title,target_amount,current_amount,end_date',
            'sort_order' => 'string|in:asc,desc',
        ]);

        $query = Campaign::with(['category', 'user'])->withCount('donations');
        // No status filtering for admin - they can see all campaigns including drafts

        // Apply filters
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%")
                  ->orWhereHas('user', function ($userQuery) use ($search) {
                      $userQuery->where('name', 'ILIKE', "%{$search}%")
                               ->orWhere('email', 'ILIKE', "%{$search}%");
                  });
            });
        }

        if ($request->filled('featured')) {
            $query->where('featured', $request->boolean('featured'));
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    /**
     * Get campaigns for the authenticated user.
     */
    public function myCampaigns(Request $request)
    {
        // Validate pagination and filtering parameters
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
            'status' => 'string|in:active,completed,cancelled,draft',
            'search' => 'string|max:255',
            'sort_by' => 'string|in:created_at,title,target_amount,current_amount,end_date',
            'sort_order' => 'string|in:asc,desc',
        ]);

        $query = Campaign::with(['category', 'user'])
            ->where('user_id', $request->user()->id);

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'ILIKE', "%{$search}%")
                  ->orWhere('description', 'ILIKE', "%{$search}%");
            });
        }

        // Sorting
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        $campaigns = $query->paginate($request->get('per_page', 15));

        return response()->json($campaigns);
    }

    /**
     * Store a newly created campaign.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'target_amount' => 'required|numeric|min:1',
            'start_date' => 'required|date|after_or_equal:today',
            'end_date' => 'required|date|after:start_date',
            'category_id' => 'required|exists:campaign_categories,id',
        ]);

        $campaign = Campaign::create([
            'title' => $request->title,
            'description' => $request->description,
            'target_amount' => $request->target_amount,
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'category_id' => $request->category_id,
            'user_id' => $request->user()->id,
            'status' => 'draft', // All user-created campaigns start as drafts for admin approval
        ]);

        // Log the campaign creation
        AuditLog::createLog(
            $request->user()->id,
            'campaign_created',
            'App\Models\Campaign',
            $campaign->id,
            null,
            $campaign->toArray(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Campaign created successfully',
            'campaign' => $campaign->load(['category', 'user']),
        ], 201);
    }

    /**
     * Display the specified campaign.
     */
    public function show(Campaign $campaign)
    {
        // Update campaign status based on current state
        $campaign->updateStatus();
        
        $campaign->load(['category', 'user', 'donations.user']);
        
        return response()->json([
            'campaign' => $campaign,
            'stats' => [
                'total_donations' => $campaign->donations()->where('status', 'completed')->count(),
                'total_donated' => $campaign->donations()->where('status', 'completed')->sum('amount'),
                'progress_percentage' => $campaign->progress_percentage,
                'days_remaining' => max(0, now()->diffInDays($campaign->end_date, false)),
            ],
        ]);
    }

    /**
     * Update the specified campaign.
     */
    public function update(Request $request, Campaign $campaign)
    {
        // Check if user owns the campaign or is admin
        if ($campaign->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validationRules = [
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'target_amount' => 'sometimes|required|numeric|min:1',
            'start_date' => 'sometimes|required|date',
            'end_date' => 'sometimes|required|date',
            'category_id' => 'sometimes|required|exists:campaign_categories,id',
        ];

        // Only admins can change status
        if ($request->user()->is_admin) {
            $validationRules['status'] = 'sometimes|in:draft,active,completed';
        }

        $request->validate($validationRules);

        // Additional validation for date relationship if both dates are being updated
        if ($request->has('start_date') && $request->has('end_date')) {
            if (strtotime($request->end_date) <= strtotime($request->start_date)) {
                return response()->json([
                    'message' => 'The end date must be after the start date.',
                    'errors' => ['end_date' => ['The end date must be after the start date.']]
                ], 422);
            }
        }

        $oldValues = $campaign->toArray();
        
        // Define updateable fields for regular users
        $updateableFields = ['title', 'description', 'target_amount', 'start_date', 'end_date', 'category_id'];
        
        // Add status to updateable fields only for admins
        if ($request->user()->is_admin && $request->has('status')) {
            $updateableFields[] = 'status';
        }
        
        $campaign->update($request->only($updateableFields));

        // Log the campaign update
        AuditLog::createLog(
            $request->user()->id,
            'campaign_updated',
            'App\Models\Campaign',
            $campaign->id,
            $oldValues,
            $campaign->toArray(),
            $request->ip(),
            $request->userAgent()
        );

        return response()->json([
            'message' => 'Campaign updated successfully',
            'campaign' => $campaign->load(['category', 'user']),
        ]);
    }

    /**
     * Remove the specified campaign.
     */
    public function destroy(Request $request, Campaign $campaign)
    {
        // Check if user owns the campaign or is admin
        if ($campaign->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Can't delete if there are donations
        if ($campaign->donations()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete campaign with existing donations'
            ], 422);
        }

        // Log the campaign deletion
        AuditLog::createLog(
            $request->user()->id,
            'campaign_deleted',
            'App\Models\Campaign',
            $campaign->id,
            $campaign->toArray(),
            null,
            $request->ip(),
            $request->userAgent()
        );

        $campaign->delete();

        return response()->noContent();
    }

    /**
     * Get trending campaigns.
     */
    public function trending()
    {
        $campaigns = Campaign::with(['category', 'user'])
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->withCount('donations')
            ->orderBy('donations_count', 'desc')
            ->orderBy('current_amount', 'desc')
            ->limit(10)
            ->get();

        // Return consistent format matching paginated responses
        return response()->json([
            'data' => $campaigns,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $campaigns->count(),
            'total' => $campaigns->count(),
            'from' => $campaigns->count() > 0 ? 1 : null,
            'to' => $campaigns->count(),
        ]);
    }

    /**
     * Get campaigns ending soon.
     */
    public function endingSoon()
    {
        $campaigns = Campaign::with(['category', 'user'])
            ->where('status', 'active')
            ->where('start_date', '<=', now())
            ->where('end_date', '>=', now())
            ->where('end_date', '<=', now()->addDays(7))
            ->orderBy('end_date', 'asc')
            ->limit(10)
            ->get();

        // Return consistent format matching paginated responses
        return response()->json([
            'data' => $campaigns,
            'current_page' => 1,
            'last_page' => 1,
            'per_page' => $campaigns->count(),
            'total' => $campaigns->count(),
            'from' => $campaigns->count() > 0 ? 1 : null,
            'to' => $campaigns->count(),
        ]);
    }

    /**
     * Get campaign statistics by status.
     * 
     * Returns the count of campaigns grouped by their status (active, completed, cancelled, draft),
     * plus total count and featured campaigns count. This endpoint provides efficient statistics 
     * without fetching full campaign data.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function stats()
    {
        $stats = Campaign::selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        // Ensure all statuses are present with 0 counts if they don't exist
        $allStatuses = ['active', 'completed', 'cancelled', 'draft'];
        $result = [];
        
        foreach ($allStatuses as $status) {
            $result[$status] = $stats[$status] ?? 0;
        }

        // Add total count
        $result['total'] = array_sum($result);

        // Add featured campaigns count
        $result['featured'] = Campaign::where('featured', true)->count();

        return response()->json($result);
    }

    /**
     * Get total amount raised across all campaigns.
     * 
     * Returns the total amount raised from all completed donations across all campaigns.
     * This provides a simple platform-wide total without campaign breakdowns.
     * 
     * @return \Illuminate\Http\JsonResponse
     */
    public function totalRaised()
    {
        $totalRaised = Donation::where('status', 'completed')->sum('amount');
        $totalDonations = Donation::where('status', 'completed')->count();
        $avgDonation = $totalDonations > 0 ? $totalRaised / $totalDonations : 0;

        return response()->json([
            'total_raised' => number_format($totalRaised, 2, '.', ''),
            'total_donations' => $totalDonations,
            'average_donation' => number_format($avgDonation, 2, '.', ''),
        ]);
    }
}
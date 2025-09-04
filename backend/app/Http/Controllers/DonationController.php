<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Donation;
use App\Services\DonationService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    /**
     * Get user's donation statistics.
     */
    public function stats(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $userId = $user->id;

        $stats = [
            'total_donated' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->sum('amount'),
            'total_donations' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->count(),
            'campaigns_supported' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->distinct('campaign_id')
                ->count(),
            'avg_donation' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->avg('amount') ?? 0,
            'first_donation' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->orderBy('created_at', 'asc')
                ->first()?->created_at,
            'last_donation' => Donation::where('user_id', $userId)
                ->where('status', 'completed')
                ->orderBy('created_at', 'desc')
                ->first()?->created_at,
            'pending_donations' => Donation::where('user_id', $userId)
                ->where('status', 'pending')
                ->count(),
            'failed_donations' => Donation::where('user_id', $userId)
                ->where('status', 'failed')
                ->count(),
        ];

        return response()->json($stats);
    }

    /**
     * Display a listing of user's donations.
     */
    public function index(Request $request): \Illuminate\Http\JsonResponse
    {
        // Validate pagination parameters
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        $donations = Donation::with(['campaign', 'campaign.category'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($donations);
    }

    /**
     * Display all donations (admin only).
     */
    public function all(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Check if user is admin
        if (! $user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate pagination parameters
        $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $donations = Donation::with(['campaign', 'campaign.category', 'user'])
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($donations);
    }

    /**
     * Store a newly created donation.
     */
    public function store(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate([
            'campaign_id' => [
                'required',
                'exists:campaigns,id',
                Rule::exists('campaigns', 'id')->where(function ($query): void {
                    $query->where('status', 'active');
                }),
            ],
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:credit_card,debit_card,paypal,bank_transfer,mock,stripe',
            'anonymous' => 'sometimes|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        /** @var \App\Models\Campaign|null $campaign */
        $campaign = Campaign::find($request->campaign_id);
        if (! $campaign) {
            return response()->json(['message' => 'Campaign not found'], 404);
        }

        // Check if campaign is active and accepting donations
        if ($campaign->status !== 'active') {
            return response()->json([
                'message' => 'This campaign is not currently accepting donations',
            ], 422);
        }

        if ($campaign->end_date < now()) {
            return response()->json([
                'message' => 'This campaign has ended',
            ], 422);
        }

        try {
            // Use DonationService to create donation and process payment
            $donationService = app(DonationService::class);

            $donationData = [
                'campaign_id' => $request->campaign_id,
                'amount' => $request->amount,
                'payment_method' => $request->payment_method,
                'anonymous' => $request->get('anonymous', false),
                'message' => $request->message,
                'provider' => 'mock', // For now, using mock provider
            ];

            $user = $request->user();
            if (! $user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            $donation = $donationService->createDonation($donationData, $user);
            if (! $donation) {
                return response()->json(['message' => 'Failed to create donation'], 500);
            }

            // Log the donation
            AuditLog::createLog(
                $user->id,
                'donation_created',
                'App\Models\Donation',
                $donation->id,
                null,
                $donation->toArray(),
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($donation->load(['campaign', 'paymentTransaction']), 201);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'message' => 'Failed to process donation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified donation.
     */
    public function show(Request $request, Donation $donation): \Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Check if user owns the donation or is admin
        if ($donation->user_id !== $user->id && ! $user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $donation->load(['campaign', 'campaign.category', 'paymentTransaction']);

        return response()->json($donation);
    }

    /**
     * Get donation receipt.
     */
    public function receipt(Request $request, \App\Models\Donation $donation): \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json(['message' => 'User not authenticated'], 401);
        }

        // Check if user owns the donation or is admin
        if ($donation->user_id !== $user->id && ! $user->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($donation->status !== 'completed') {
            return response()->json([
                'message' => 'Receipt is only available for completed donations',
            ], 403);
        }

        $donation->load(['campaign', 'campaign.category', 'user', 'paymentTransaction']);

        $receipt = [
            'donation_id' => $donation->id,
            'receipt_number' => 'RCP_'.$donation->id.'_'.now()->format('Ymd'),
            'amount' => $donation->amount,
            'date' => $donation->created_at?->format('F j, Y') ?? 'Unknown',
            'campaign' => [
                'id' => $donation->campaign->id,
                'title' => $donation->campaign->title,
                'category' => $donation->campaign->category->name,
            ],
            'donor' => $donation->anonymous ? 'Anonymous' : [
                'name' => $donation->user->name,
                'employee_id' => $donation->user->employee_id,
            ],
            'payment_method' => $donation->payment_method,
            'transaction_id' => $donation->transaction_id,
            'issued_at' => now()->format('F j, Y \a\t g:i A'),
            'organization' => [
                'name' => 'ACME Corporation',
                'address' => '123 Business Street, Corporate City, CC 12345',
                'tax_id' => 'TAX123456789',
            ],
        ];

        // Generate PDF
        try {
            $pdf = Pdf::loadView('receipts.donation', ['receipt' => $receipt]);

            // Return PDF as file download
            return response($pdf->output())
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'attachment; filename="receipt-'.$donation->id.'.pdf"');
        } catch (\Exception $e) {
            \Log::error('Failed to generate PDF receipt: '.$e->getMessage());

            return response()->json([
                'message' => 'Failed to generate receipt PDF',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Campaign;
use App\Models\Donation;
use App\Models\PaymentTransaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class DonationController extends Controller
{
    /**
     * Display a listing of user's donations.
     */
    public function index(Request $request)
    {
        // Validate pagination parameters
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'per_page' => 'integer|min:1|max:100',
        ]);

        $donations = Donation::with(['campaign', 'campaign.category'])
            ->where('user_id', $request->user()->id)
            ->orderBy('created_at', 'desc')
            ->paginate($request->get('per_page', 15));

        return response()->json($donations);
    }

    /**
     * Display all donations (admin only).
     */
    public function all(Request $request)
    {
        // Check if user is admin
        if (!$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // Validate pagination parameters
        $validated = $request->validate([
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
    public function store(Request $request)
    {
        $request->validate([
            'campaign_id' => [
                'required',
                'exists:campaigns,id',
                Rule::exists('campaigns', 'id')->where(function ($query) {
                    $query->where('status', 'active');
                }),
            ],
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'required|string|in:credit_card,debit_card,paypal,bank_transfer,mock,stripe',
            'anonymous' => 'sometimes|boolean',
            'message' => 'nullable|string|max:500',
        ]);

        $campaign = Campaign::find($request->campaign_id);

        // Check if campaign is active and accepting donations
        if ($campaign->status !== 'active') {
            return response()->json([
                'message' => 'This campaign is not currently accepting donations'
            ], 422);
        }

        if ($campaign->end_date < now()) {
            return response()->json([
                'message' => 'This campaign has ended'
            ], 422);
        }

        try {
            DB::beginTransaction();

            // Create donation record
            $donation = Donation::create([
                'amount' => $request->amount,
                'campaign_id' => $request->campaign_id,
                'user_id' => $request->user()->id,
                'payment_method' => $request->payment_method,
                'transaction_id' => 'TXN_' . Str::upper(Str::random(10)),
                'status' => 'pending',
                'anonymous' => $request->get('anonymous', false),
                'message' => $request->message,
            ]);

            // Create payment transaction record
            $paymentTransaction = PaymentTransaction::create([
                'donation_id' => $donation->id,
                'provider' => 'mock', // For now, using mock provider
                'external_transaction_id' => 'EXT_' . Str::upper(Str::random(12)),
                'amount' => $request->amount,
                'currency' => 'USD',
                'status' => 'pending',
                'response_data' => [
                    'payment_method' => $request->payment_method,
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            // For demo purposes, automatically approve the payment
            $this->processPayment($donation, $paymentTransaction);

            DB::commit();

            // Log the donation
            AuditLog::createLog(
                $request->user()->id,
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
    public function show(Request $request, Donation $donation)
    {
        // Check if user owns the donation or is admin
        if ($donation->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $donation->load(['campaign', 'campaign.category', 'paymentTransaction']);

        return response()->json($donation);
    }

    /**
     * Get donation receipt.
     */
    public function receipt(Request $request, \App\Models\Donation $donation)
    {
        // Check if user owns the donation or is admin
        if ($donation->user_id !== $request->user()->id && !$request->user()->is_admin) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($donation->status !== 'completed') {
            return response()->json([
                'message' => 'Receipt is only available for completed donations'
            ], 403);
        }

        $donation->load(['campaign', 'campaign.category', 'user', 'paymentTransaction']);

        $receipt = [
            'donation_id' => $donation->id,
            'receipt_number' => 'RCP_' . $donation->id . '_' . now()->format('Ymd'),
            'amount' => $donation->amount,
            'date' => $donation->created_at->toISOString(),
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
            'issued_at' => now()->toISOString(),
            'organization' => [
                'name' => 'ACME Corporation',
                'address' => '123 Business Street, Corporate City, CC 12345',
                'tax_id' => 'TAX123456789',
            ],
        ];

        return response()->json($receipt);
    }

    /**
     * Process payment (mock implementation).
     */
    private function processPayment(Donation $donation, PaymentTransaction $transaction)
    {
        // Mock payment processing - in real implementation, this would call actual payment providers
        
        // Simulate payment success (90% success rate for demo, 100% in testing)
        $success = app()->environment('testing') ? true : rand(1, 10) <= 9;

        if ($success) {
            // Update donation status
            $donation->update(['status' => 'completed']);
            
            // Update payment transaction
            $transaction->update([
                'status' => 'completed',
                'response_data' => array_merge($transaction->response_data ?? [], [
                    'processed_at' => now()->toISOString(),
                    'confirmation_code' => 'CONF_' . Str::upper(Str::random(8)),
                ]),
            ]);

            // Update campaign current amount
            /** @var \App\Models\Campaign $campaign */
            $campaign = $donation->campaign;
            $campaign->increment('current_amount', $donation->amount);

            // Check if campaign target is reached
            if ($campaign->current_amount >= $campaign->target_amount) {
                $campaign->update(['status' => 'completed']);
            }
        } else {
            // Payment failed
            $donation->update(['status' => 'failed']);
            $transaction->update([
                'status' => 'failed',
                'response_data' => array_merge($transaction->response_data ?? [], [
                    'failed_at' => now()->toISOString(),
                    'error_message' => 'Payment processing failed',
                ]),
            ]);
        }
    }
}
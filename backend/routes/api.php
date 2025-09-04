<?php

use App\Http\Controllers\Admin\AdminAnalyticsController;
use App\Http\Controllers\Admin\AdminReportController;
use App\Http\Controllers\Admin\AdminUserController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\LogoutController;
use App\Http\Controllers\Auth\ProfileController;
use App\Http\Controllers\CampaignCategoryController;
use App\Http\Controllers\CampaignController;
use App\Http\Controllers\DonationController;
use App\Models\AuditLog;
use App\Models\Campaign;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::post('/login', [LoginController::class, 'login']);

// Public campaign routes - specific routes before parameterized ones
Route::get('/campaigns', [CampaignController::class, 'index']);
Route::get('/campaigns/stats', [CampaignController::class, 'stats']);
Route::get('/campaigns/total-raised', [CampaignController::class, 'totalRaised']);
Route::get('/campaigns/trending', [CampaignController::class, 'trending']);
Route::get('/campaigns/ending-soon', [CampaignController::class, 'endingSoon']);
Route::get('/campaigns/{campaign}', [CampaignController::class, 'show']);

// Public category routes
Route::get('/categories', [CampaignCategoryController::class, 'index']);
Route::get('/categories/{campaignCategory}', [CampaignCategoryController::class, 'show']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User routes
    Route::get('/user', [ProfileController::class, 'show']);
    Route::put('/profile', [ProfileController::class, 'update']);
    Route::put('/profile/password', [ProfileController::class, 'updatePassword']);

    // Logout routes
    Route::post('/logout', [LogoutController::class, 'logout']);
    Route::post('/logout-all', [LogoutController::class, 'logoutAll']);

    // Campaign management routes - specific routes before parameterized ones
    Route::get('/campaigns/my-campaigns', [CampaignController::class, 'myCampaigns']);
    Route::put('/campaigns/{campaign}', [CampaignController::class, 'update']);
    Route::post('/campaigns', [CampaignController::class, 'store']);
    Route::delete('/campaigns/{campaign}', [CampaignController::class, 'destroy']);

    // Donation routes
    Route::get('/donations/my-stats', [DonationController::class, 'stats']);
    Route::get('/donations/my-donations', [DonationController::class, 'index']);
    Route::get('/donations/{donation}/receipt', [DonationController::class, 'receipt']);
    Route::get('/donations/{donation}', [DonationController::class, 'show']);
    Route::post('/donations', [DonationController::class, 'store']);

    // Admin routes (require admin middleware)
    Route::middleware('admin')->prefix('admin')->group(function () {
        // Analytics & Dashboard
        Route::get('/dashboard', [AdminAnalyticsController::class, 'dashboard']);
        Route::get('/analytics/donations', [AdminAnalyticsController::class, 'donationAnalytics']);
        Route::get('/analytics/campaigns', [AdminAnalyticsController::class, 'campaignAnalytics']);
        Route::get('/analytics/users', [AdminAnalyticsController::class, 'userAnalytics']);

        // User Management
        Route::get('/users', [AdminUserController::class, 'index']);
        Route::get('/users/statistics', [AdminUserController::class, 'statistics']);
        Route::get('/users/{user}', [AdminUserController::class, 'show']);
        Route::get('/export/users', [AdminUserController::class, 'export']);
        Route::put('/users/{user}', [AdminUserController::class, 'update']);
        Route::post('/users', [AdminUserController::class, 'store']);
        Route::post('/users/bulk-update', [AdminUserController::class, 'bulkUpdate']);
        Route::delete('/users/{user}', [AdminUserController::class, 'destroy']);

        // Reports & Exports
        Route::get('/reports/financial', [AdminReportController::class, 'financialReport']);
        Route::get('/reports/campaigns', [AdminReportController::class, 'campaignReport']);
        Route::get('/reports/user-engagement', [AdminReportController::class, 'userEngagementReport']);
        Route::get('/reports/impact', [AdminReportController::class, 'impactReport']);
        Route::post('/export', [AdminReportController::class, 'exportData']);

        // Admin Campaign Management (additional admin features)
        Route::get('/campaigns', [CampaignController::class, 'adminIndex']); // Admin campaigns list with all statuses
        Route::put('/campaigns/{campaign}/featured', function (Request $request, Campaign $campaign) {
            $campaign->update(['featured' => $request->boolean('featured')]);

            AuditLog::createLog(
                $request->user()?->id,
                'campaign_featured_updated',
                'App\Models\Campaign',
                $campaign->id,
                ['featured' => ! $request->boolean('featured')],
                ['featured' => $request->boolean('featured')],
                $request->ip(),
                $request->userAgent()
            );

            return response()->json($campaign);
        });

        // Admin Category Management
        Route::put('/categories/{campaignCategory}', [CampaignCategoryController::class, 'update']);
        Route::post('/categories', [CampaignCategoryController::class, 'store']);
        Route::delete('/categories/{campaignCategory}', [CampaignCategoryController::class, 'destroy']);

        // Admin Donation Management
        Route::get('/donations', [DonationController::class, 'all']);
    });
});

<?php

namespace App\Http\Controllers;

use App\Models\CampaignCategory;
use Illuminate\Http\Request;

class CampaignCategoryController extends Controller
{
    /**
     * Display a listing of campaign categories.
     */
    public function index()
    {
        $categories = CampaignCategory::withCount('campaigns')->get();
        
        return response()->json($categories);
    }

    /**
     * Display the specified category.
     */
    public function show(CampaignCategory $campaignCategory)
    {
        $campaignCategory->load(['campaigns' => function ($query) {
            $query->where('status', 'active')
                  ->where('start_date', '<=', now())
                  ->where('end_date', '>=', now())
                  ->with(['user'])
                  ->orderBy('created_at', 'desc');
        }]);

        return response()->json($campaignCategory);
    }
}
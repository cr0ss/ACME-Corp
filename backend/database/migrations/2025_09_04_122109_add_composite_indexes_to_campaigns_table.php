<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            // Composite indexes for common query patterns
            $table->index(['status', 'category_id', 'featured'], 'campaigns_status_category_featured_idx');
            $table->index(['status', 'start_date', 'end_date'], 'campaigns_status_dates_idx');
            $table->index(['category_id', 'status', 'created_at'], 'campaigns_category_status_created_idx');
            $table->index(['user_id', 'status', 'created_at'], 'campaigns_user_status_created_idx');

            // Text search optimization (for PostgreSQL)
            $table->index(['title', 'status'], 'campaigns_title_status_idx');
            $table->index(['description', 'status'], 'campaigns_description_status_idx');

            // Additional performance indexes
            $table->index(['status', 'created_at'], 'campaigns_status_created_idx');
            $table->index(['featured', 'status', 'created_at'], 'campaigns_featured_status_created_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_status_category_featured_idx');
            $table->dropIndex('campaigns_status_dates_idx');
            $table->dropIndex('campaigns_category_status_created_idx');
            $table->dropIndex('campaigns_user_status_created_idx');
            $table->dropIndex('campaigns_title_status_idx');
            $table->dropIndex('campaigns_description_status_idx');
            $table->dropIndex('campaigns_status_created_idx');
            $table->dropIndex('campaigns_featured_status_created_idx');
        });
    }
};

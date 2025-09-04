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
            // Index for featured campaigns queries
            $table->index('featured', 'campaigns_featured_idx');

            // Composite index for featured + status queries
            $table->index(['featured', 'status'], 'campaigns_featured_status_idx');
        });

        Schema::table('donations', function (Blueprint $table) {
            // Index for donation status queries (used in totalRaised)
            $table->index('status', 'donations_status_idx');

            // Composite index for status + amount queries
            $table->index(['status', 'amount'], 'donations_status_amount_idx');

            // Covering index for totalRaised query (status + amount + id for COUNT)
            $table->index(['status', 'amount', 'id'], 'donations_status_amount_covering_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('campaigns', function (Blueprint $table) {
            $table->dropIndex('campaigns_featured_idx');
            $table->dropIndex('campaigns_featured_status_idx');
        });

        Schema::table('donations', function (Blueprint $table) {
            $table->dropIndex('donations_status_idx');
            $table->dropIndex('donations_status_amount_idx');
            $table->dropIndex('donations_status_amount_covering_idx');
        });
    }
};

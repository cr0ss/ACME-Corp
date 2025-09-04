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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description');
            $table->decimal('target_amount', 10, 2);
            $table->decimal('current_amount', 10, 2)->default(0);
            $table->date('start_date');
            $table->date('end_date');
            $table->enum('status', ['draft', 'active', 'completed', 'cancelled'])->default('draft');
            $table->foreignId('category_id')->constrained('campaign_categories');
            $table->foreignId('user_id')->constrained('users'); // creator
            $table->boolean('featured')->default(false);
            $table->timestamps();

            // Basic indexes
            $table->index(['status', 'featured']);
            $table->index(['start_date', 'end_date']);
            $table->index('category_id');
            $table->index('user_id');

            // Composite indexes for common query patterns
            $table->index(['status', 'category_id', 'featured']);
            $table->index(['status', 'start_date', 'end_date']);
            $table->index(['category_id', 'status', 'created_at']);
            $table->index(['user_id', 'status', 'created_at']);

            // Text search optimization (for PostgreSQL)
            $table->index(['title', 'status']);
            $table->index(['description', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};

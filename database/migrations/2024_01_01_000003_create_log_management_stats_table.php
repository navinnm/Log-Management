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
        Schema::create('log_management_stats', function (Blueprint $table) {
            $table->id();
            $table->date('date')->index();
            $table->string('metric_type', 50)->index(); // daily_summary, hourly_summary, etc.
            $table->string('metric_name', 100)->index(); // total_logs, errors_count, notifications_sent, etc.
            $table->string('dimension_key', 100)->nullable()->index(); // level, channel, environment, etc.
            $table->string('dimension_value', 100)->nullable()->index(); // error, slack, production, etc.
            $table->bigInteger('count')->default(0);
            $table->decimal('sum_value', 15, 2)->nullable();
            $table->decimal('avg_value', 10, 4)->nullable();
            $table->decimal('min_value', 10, 4)->nullable();
            $table->decimal('max_value', 10, 4)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Unique constraint to prevent duplicate entries
            $table->unique([
                'date', 
                'metric_type', 
                'metric_name', 
                'dimension_key', 
                'dimension_value'
            ], 'log_stats_unique_idx');

            // Indexes for queries
            $table->index(['date', 'metric_type', 'metric_name']);
            $table->index(['metric_type', 'metric_name', 'date']);
            $table->index(['dimension_key', 'dimension_value', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_management_stats');
    }
};
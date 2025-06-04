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
        Schema::create('log_alert_incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('alert_id')->constrained('log_alerts')->onDelete('cascade');
            $table->string('incident_key', 100)->unique(); // Unique identifier for grouping
            $table->string('status', 20)->default('open')->index(); // open, acknowledged, resolved
            $table->string('severity', 20); // Inherited from alert or escalated
            $table->text('title');
            $table->text('description')->nullable();
            $table->json('trigger_data'); // Data that triggered the alert
            $table->json('affected_logs'); // IDs of logs that triggered this incident
            $table->integer('occurrence_count')->default(1);
            $table->timestamp('first_occurrence_at')->index();
            $table->timestamp('last_occurrence_at')->index();
            $table->timestamp('acknowledged_at')->nullable();
            $table->string('acknowledged_by')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            $table->string('resolved_by')->nullable();
            $table->text('resolution_notes')->nullable();
            $table->boolean('auto_resolved')->default(false);
            $table->json('notifications_sent')->nullable(); // Track which notifications were sent
            $table->json('escalations')->nullable(); // Track escalation history
            $table->json('metadata')->nullable();
            $table->timestamps();

            // Indexes
            $table->index(['alert_id', 'status']);
            $table->index(['status', 'first_occurrence_at']);
            $table->index(['severity', 'status']);
            $table->index('incident_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_alert_incidents');
    }
};
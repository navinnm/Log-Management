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
        Schema::create('log_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->text('description')->nullable();
            $table->boolean('enabled')->default(true)->index();
            $table->string('trigger_type', 50)->default('threshold'); // threshold, pattern, anomaly
            $table->json('conditions'); // Alert conditions
            $table->json('thresholds'); // Threshold values
            $table->string('time_window', 20)->default('5m'); // 5m, 1h, 1d, etc.
            $table->string('severity', 20)->default('medium'); // low, medium, high, critical
            $table->json('notification_channels'); // Which channels to notify
            $table->json('escalation_rules')->nullable(); // Escalation configuration
            $table->boolean('auto_resolve')->default(false);
            $table->integer('resolve_threshold')->nullable();
            $table->timestamp('last_triggered_at')->nullable()->index();
            $table->timestamp('last_resolved_at')->nullable();
            $table->string('status', 20)->default('active')->index(); // active, triggered, resolved, disabled
            $table->integer('trigger_count')->default(0);
            $table->json('metadata')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Composite indexes only (single column indexes already defined above)
            $table->index(['enabled', 'status'], 'log_alerts_enabled_status_index');
            $table->index(['trigger_type', 'enabled'], 'log_alerts_trigger_type_enabled_index');
            // Remove the duplicate last_triggered_at index since it's already indexed above
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_alerts');
    }
};
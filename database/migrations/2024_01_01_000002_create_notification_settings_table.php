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
        Schema::create('notification_settings', function (Blueprint $table) {
            $table->id();
            $table->string('channel', 50)->unique();
            $table->boolean('enabled')->default(true)->index();
            $table->json('settings')->nullable();
            $table->json('conditions')->nullable();
            $table->json('rate_limit')->nullable();
            $table->json('filters')->nullable();
            $table->integer('priority')->default(1);
            $table->timestamp('last_notification_at')->nullable(); // Remove ->index() from here
            $table->integer('notification_count')->default(0);
            $table->integer('failure_count')->default(0);
            $table->timestamp('last_failure_at')->nullable();
            $table->text('last_error')->nullable();
            $table->boolean('maintenance_mode')->default(false);
            $table->timestamp('maintenance_until')->nullable();
            $table->string('created_by')->nullable();
            $table->string('updated_by')->nullable();
            $table->timestamps();

            // Indexes - Use custom names to avoid conflicts
            $table->index(['enabled', 'channel'], 'ns_enabled_channel_idx');
            $table->index(['maintenance_mode', 'enabled'], 'ns_maintenance_enabled_idx');
            $table->index('last_notification_at', 'ns_last_notification_idx');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notification_settings');
    }
};
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
        Schema::create('log_entries', function (Blueprint $table) {
            $table->id();
            $table->string('level', 20)->index();
            $table->string('channel', 100)->default('default')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('extra')->nullable();
            $table->string('environment', 50)->default('production')->index();
            $table->string('user_id')->nullable()->index();
            $table->string('session_id')->nullable()->index();
            $table->string('request_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->text('url')->nullable();
            $table->string('method', 10)->nullable();
            $table->integer('status_code')->nullable();
            $table->decimal('execution_time', 8, 3)->nullable();
            $table->bigInteger('memory_usage')->nullable();
            $table->string('file_path')->nullable();
            $table->integer('line_number')->nullable();
            $table->text('stack_trace')->nullable();
            $table->json('tags')->nullable();
            $table->timestamp('created_at')->index();
            $table->timestamp('updated_at')->nullable();

            // Indexes for better performance
            $table->index(['level', 'created_at']);
            $table->index(['channel', 'created_at']);
            $table->index(['environment', 'created_at']);
            $table->index(['created_at', 'level']);
            
            // Composite indexes for common queries
            $table->index(['environment', 'level', 'created_at'], 'log_entries_env_level_date_idx');
            $table->index(['channel', 'level', 'created_at'], 'log_entries_channel_level_date_idx');
            
            // Full-text search index for message (MySQL only)
            if (config('database.default') === 'mysql') {
                $table->fullText('message', 'log_entries_message_fulltext');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('log_entries');
    }
};
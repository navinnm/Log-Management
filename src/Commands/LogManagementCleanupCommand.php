<?php

namespace Fulgid\LogManagement\Commands;

use Illuminate\Console\Command;
use Fulgid\LogManagement\Models\LogEntry;
use Carbon\Carbon;

class LogManagementCleanupCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'log-management:cleanup 
                            {--days=30 : Number of days to keep logs}
                            {--level= : Only cleanup logs of specific level}
                            {--dry-run : Show what would be deleted without actually deleting}
                            {--force : Skip confirmation prompt}';

    /**
     * The console command description.
     */
    protected $description = 'Clean up old log entries from the database';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $days = (int) $this->option('days');
        $level = $this->option('level');
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');

        if ($days <= 0) {
            $this->error('Days must be a positive number');
            return Command::FAILURE;
        }

        $this->info("Log Management Cleanup");
        $this->line("Cleaning up logs older than {$days} days");

        if ($level) {
            $this->line("Filtering by level: {$level}");
        }

        $cutoffDate = Carbon::now()->subDays($days);
        
        // Build query
        $query = LogEntry::where('created_at', '<', $cutoffDate);
        
        if ($level) {
            $query->where('level', strtolower($level));
        }

        $totalCount = $query->count();

        if ($totalCount === 0) {
            $this->info('No log entries found matching the criteria.');
            return Command::SUCCESS;
        }

        // Show statistics
        $this->displayStatistics($query->get(), $cutoffDate);

        if ($dryRun) {
            $this->warn('DRY RUN - No logs will be deleted');
            return Command::SUCCESS;
        }

        // Confirm deletion
        if (!$force && !$this->confirm("Delete {$totalCount} log entries?")) {
            $this->info('Cleanup cancelled.');
            return Command::SUCCESS;
        }

        // Perform cleanup
        $this->performCleanup($query, $totalCount);

        return Command::SUCCESS;
    }

    /**
     * Display cleanup statistics.
     */
    protected function displayStatistics($logs, Carbon $cutoffDate): void
    {
        $this->newLine();
        $this->comment('Cleanup Statistics:');
        $this->line("Cutoff date: {$cutoffDate->format('Y-m-d H:i:s')}");
        $this->line("Total logs to delete: {$logs->count()}");

        // Group by level
        $levelCounts = $logs->groupBy('level')->map->count();
        if ($levelCounts->isNotEmpty()) {
            $this->line("Breakdown by level:");
            foreach ($levelCounts as $level => $count) {
                $this->line("  {$level}: {$count}");
            }
        }

        // Group by date
        $dateCounts = $logs->groupBy(function ($log) {
            return $log->created_at->format('Y-m-d');
        })->map->count()->take(10);

        if ($dateCounts->isNotEmpty()) {
            $this->line("Breakdown by date (showing first 10):");
            foreach ($dateCounts as $date => $count) {
                $this->line("  {$date}: {$count}");
            }
        }

        $this->newLine();
    }

    /**
     * Perform the actual cleanup.
     */
    protected function performCleanup($query, int $totalCount): void
    {
        $this->info('Starting cleanup...');

        $batchSize = 1000;
        $deleted = 0;

        $progressBar = $this->output->createProgressBar($totalCount);
        $progressBar->start();

        try {
            // Delete in batches to avoid memory issues
            while (true) {
                $batch = $query->limit($batchSize)->get();
                
                if ($batch->isEmpty()) {
                    break;
                }

                $batchIds = $batch->pluck('id');
                $deletedInBatch = LogEntry::whereIn('id', $batchIds)->delete();
                
                $deleted += $deletedInBatch;
                $progressBar->advance($deletedInBatch);

                // Small delay to prevent overwhelming the database
                usleep(10000); // 10ms
            }

            $progressBar->finish();
            $this->newLine(2);

            $this->info("✅ Cleanup completed successfully!");
            $this->line("Deleted {$deleted} log entries");

            // Show remaining log count
            $remaining = LogEntry::count();
            $this->line("Remaining log entries: {$remaining}");

        } catch (\Exception $e) {
            $progressBar->finish();
            $this->newLine(2);
            
            $this->error("❌ Cleanup failed: " . $e->getMessage());
            $this->line("Deleted {$deleted} entries before failure");
            
            return;
        }

        // Optimize database tables after cleanup
        if ($this->confirm('Optimize database tables after cleanup?', true)) {
            $this->optimizeTables();
        }
    }

    /**
     * Optimize database tables after cleanup.
     */
    protected function optimizeTables(): void
    {
        $this->info('Optimizing database tables...');

        try {
            $connection = config('database.default');
            $driver = config("database.connections.{$connection}.driver");

            if ($driver === 'mysql') {
                \DB::statement('OPTIMIZE TABLE log_entries');
                \DB::statement('OPTIMIZE TABLE notification_settings');
                $this->line('✅ MySQL tables optimized');
            } else {
                $this->line('ℹ️  Table optimization not available for ' . $driver);
            }
        } catch (\Exception $e) {
            $this->warn('Failed to optimize tables: ' . $e->getMessage());
        }
    }
}
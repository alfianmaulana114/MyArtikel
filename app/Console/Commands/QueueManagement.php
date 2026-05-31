<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QueueManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'queue:manage
                            {action : Action to perform (status|failed|retry|flush|monitor)}
                            {--queue= : Specific queue to manage}
                            {--job-id= : Specific job ID for retry}
                            {--all : Apply to all items}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Comprehensive queue management command';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');
        $queue = $this->option('queue');

        $this->info("Queue Management - Action: {$action}".($queue ? ", Queue: {$queue}" : ''));

        switch ($action) {
            case 'status':
                $this->showStatus($queue);
                break;

            case 'failed':
                $this->showFailedJobs($queue);
                break;

            case 'retry':
                $this->retryJobs($queue);
                break;

            case 'flush':
                $this->flushFailedJobs();
                break;

            case 'monitor':
                $this->monitorQueue($queue);
                break;

            default:
                $this->error("Invalid action: {$action}. Use: status, failed, retry, flush, or monitor");

                return 1;
        }

        return 0;
    }

    /**
     * Show queue status
     */
    private function showStatus(?string $queue): void
    {
        $this->info('Queue Status'.($queue ? " for: {$queue}" : ''));

        // Pending jobs
        $pendingQuery = DB::table('jobs');
        if ($queue) {
            $pendingQuery->where('queue', $queue);
        }
        $pendingCount = $pendingQuery->count();

        $this->info("Pending Jobs: {$pendingCount}");

        // Show pending jobs by queue
        $pendingByQueue = DB::table('jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->orderBy('count', 'desc')
            ->get();

        if ($pendingByQueue->isNotEmpty()) {
            $this->table(['Queue', 'Count'], $pendingByQueue->toArray());
        }

        // Failed jobs
        $failedQuery = DB::table('failed_jobs');
        if ($queue) {
            $failedQuery->where('queue', $queue);
        }
        $failedCount = $failedQuery->count();

        $this->info("Failed Jobs: {$failedCount}");

        // Show failed jobs by queue
        $failedByQueue = DB::table('failed_jobs')
            ->select('queue', DB::raw('COUNT(*) as count'))
            ->groupBy('queue')
            ->orderBy('count', 'desc')
            ->get();

        if ($failedByQueue->isNotEmpty()) {
            $this->table(['Queue', 'Count'], $failedByQueue->toArray());
        }

        // Processing batches
        $processingBatches = DB::table('job_batches')
            ->whereNull('finished_at')
            ->count();

        $this->info("Processing Batches: {$processingBatches}");

        // Recent activity
        $recentActivity = DB::table('job_batches')
            ->where('created_at', '>', now()->subHour())
            ->selectRaw('COUNT(*) as total_batches')
            ->selectRaw('SUM(total_jobs) as total_jobs')
            ->selectRaw('SUM(failed_jobs) as failed_jobs')
            ->first();

        if ($recentActivity && $recentActivity->total_batches > 0) {
            $this->info('Recent Activity (last hour):');
            $this->line("  Batches: {$recentActivity->total_batches}");
            $this->line("  Jobs: {$recentActivity->total_jobs}");
            $this->line("  Failed: {$recentActivity->failed_jobs}");
        }
    }

    /**
     * Show failed jobs
     */
    private function showFailedJobs(?string $queue): void
    {
        $this->info('Failed Jobs'.($queue ? " for queue: {$queue}" : ''));

        $failedJobs = DB::table('failed_jobs')
            ->when($queue, function ($query) use ($queue) {
                return $query->where('queue', $queue);
            })
            ->orderBy('failed_at', 'desc')
            ->limit(20)
            ->get();

        if ($failedJobs->isEmpty()) {
            $this->info('No failed jobs found');

            return;
        }

        $headers = ['ID', 'Queue', 'Failed At', 'Exception'];
        $rows = [];

        foreach ($failedJobs as $job) {
            $payload = json_decode($job->payload, true);
            $jobClass = $payload['displayName'] ?? 'Unknown';
            $exception = explode("\n", $job->exception)[0] ?? 'Unknown';

            $rows[] = [
                $job->id,
                $job->queue,
                $job->failed_at,
                substr($exception, 0, 50).(strlen($exception) > 50 ? '...' : ''),
            ];
        }

        $this->table($headers, $rows);

        if ($failedJobs->count() === 20) {
            $this->warn('Showing only the 20 most recent failed jobs');
        }
    }

    /**
     * Retry failed jobs
     */
    private function retryJobs(?string $queue): void
    {
        $jobId = $this->option('job-id');
        $all = $this->option('all');

        if (! $jobId && ! $all) {
            $this->error('Please specify --job-id or --all');

            return;
        }

        try {
            if ($all) {
                $this->info('Retrying all failed jobs'.($queue ? " for queue: {$queue}" : ''));

                $count = DB::table('failed_jobs')
                    ->when($queue, function ($query) use ($queue) {
                        return $query->where('queue', $queue);
                    })
                    ->count();

                if ($count === 0) {
                    $this->info('No failed jobs to retry');

                    return;
                }

                Artisan::call('queue:retry', ['id' => 'all']);
                $this->info("Queued {$count} failed jobs for retry");

            } elseif ($jobId) {
                $this->info("Retrying job ID: {$jobId}");

                $job = DB::table('failed_jobs')->find($jobId);
                if (! $job) {
                    $this->error("Job not found: {$jobId}");

                    return;
                }

                Artisan::call('queue:retry', ['id' => $jobId]);
                $this->info("Job {$jobId} queued for retry");
            }

        } catch (Exception $e) {
            $this->error('Failed to retry jobs: '.$e->getMessage());
            Log::error('Queue retry failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Flush failed jobs
     */
    private function flushFailedJobs(): void
    {
        $this->warn('This will permanently delete all failed jobs!');

        if (! $this->confirm('Are you sure you want to continue?')) {
            $this->info('Operation cancelled');

            return;
        }

        try {
            $count = DB::table('failed_jobs')->count();

            if ($count === 0) {
                $this->info('No failed jobs to flush');

                return;
            }

            Artisan::call('queue:flush');
            $this->info("Flushed {$count} failed jobs");

            Log::info('Failed jobs flushed', ['count' => $count]);

        } catch (Exception $e) {
            $this->error('Failed to flush jobs: '.$e->getMessage());
            Log::error('Queue flush failed', ['error' => $e->getMessage()]);
        }
    }

    /**
     * Monitor queue in real-time
     */
    private function monitorQueue(?string $queue): void
    {
        $this->info('Monitoring queue'.($queue ? " {$queue}" : '').' - Press Ctrl+C to stop');

        $startTime = time();
        $refreshInterval = 2; // seconds

        while (true) {
            try {
                // Clear screen
                if (PHP_OS_FAMILY !== 'Windows') {
                    system('clear');
                }

                $this->showStatus($queue);

                // Show recent activity
                $recentJobs = DB::table('job_batches')
                    ->where('created_at', '>', now()->subMinutes(5))
                    ->orderBy('created_at', 'desc')
                    ->limit(5)
                    ->get();

                if ($recentJobs->isNotEmpty()) {
                    $this->info("\nRecent Activity:");
                    foreach ($recentJobs as $batch) {
                        $status = $batch->finished_at ? 'Completed' : 'Processing';
                        $duration = $batch->finished_at
                            ? round(Carbon\Carbon::parse($batch->finished_at)->diffInSeconds(Carbon\Carbon::parse($batch->created_at)))
                            : 'N/A';

                        $this->line("  Batch {$batch->id}: {$status} ({$batch->total_jobs} jobs, {$duration}s)");
                    }
                }

                $this->info("\nMonitoring for: ".(time() - $startTime).' seconds');
                $this->info("Next update in {$refreshInterval} seconds...");

                sleep($refreshInterval);

            } catch (Exception $e) {
                $this->error('Monitor error: '.$e->getMessage());
                sleep($refreshInterval);
            }
        }
    }
}

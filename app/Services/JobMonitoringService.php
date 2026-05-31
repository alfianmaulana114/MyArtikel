<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class JobMonitoringService
{
    private int $cacheTtl = 300; // 5 minutes

    /**
     * Get queue statistics
     */
    public function getQueueStatistics(): array
    {
        $cacheKey = 'queue_statistics';

        return Cache::remember($cacheKey, $this->cacheTtl, function () {
            return [
                'pending' => $this->getPendingJobsCount(),
                'processing' => $this->getProcessingJobsCount(),
                'failed' => $this->getFailedJobsCount(),
                'completed' => $this->getCompletedJobsCount(),
                'throughput' => $this->getThroughputStats(),
                'performance' => $this->getPerformanceStats(),
            ];
        });
    }

    /**
     * Get job statistics by type
     */
    public function getJobTypeStatistics(): array
    {
        $stats = [];

        // Get job types from recent jobs
        $jobTypes = $this->getRecentJobTypes();

        foreach ($jobTypes as $type) {
            $stats[$type] = [
                'pending' => $this->getJobsCountByTypeAndStatus($type, 'pending'),
                'processing' => $this->getJobsCountByTypeAndStatus($type, 'processing'),
                'failed' => $this->getJobsCountByTypeAndStatus($type, 'failed'),
                'completed' => $this->getJobsCountByTypeAndStatus($type, 'completed'),
                'avg_processing_time' => $this->getAverageProcessingTime($type),
                'success_rate' => $this->getSuccessRate($type),
            ];
        }

        return $stats;
    }

    /**
     * Get user-specific job statistics
     */
    public function getUserJobStatistics(int $userId): array
    {
        $cacheKey = "user_job_statistics_{$userId}";

        return Cache::remember($cacheKey, $this->cacheTtl, function () use ($userId) {
            return [
                'total_jobs' => $this->getUserJobsCount($userId),
                'pending_jobs' => $this->getUserJobsCountByStatus($userId, 'pending'),
                'failed_jobs' => $this->getUserJobsCountByStatus($userId, 'failed'),
                'completed_jobs' => $this->getUserJobsCountByStatus($userId, 'completed'),
                'recent_jobs' => $this->getUserRecentJobs($userId, 10),
                'job_types' => $this->getUserJobTypes($userId),
            ];
        });
    }

    /**
     * Get pending jobs count
     */
    private function getPendingJobsCount(): int
    {
        return DB::table('jobs')->count();
    }

    /**
     * Get processing jobs count
     */
    private function getProcessingJobsCount(): int
    {
        return DB::table('job_batches')
            ->where('finished_at', null)
            ->sum('pending_jobs');
    }

    /**
     * Get failed jobs count
     */
    private function getFailedJobsCount(): int
    {
        return DB::table('failed_jobs')->count();
    }

    /**
     * Get completed jobs count (estimated)
     */
    private function getCompletedJobsCount(): int
    {
        // This is an estimation based on recent activity
        return DB::table('job_batches')
            ->whereNotNull('finished_at')
            ->where('finished_at', '>', now()->subDay())
            ->sum('total_jobs');
    }

    /**
     * Get throughput statistics
     */
    private function getThroughputStats(): array
    {
        $hourly = DB::table('job_batches')
            ->where('created_at', '>', now()->subHour())
            ->whereNotNull('finished_at')
            ->sum('total_jobs');

        $daily = DB::table('job_batches')
            ->where('created_at', '>', now()->subDay())
            ->whereNotNull('finished_at')
            ->sum('total_jobs');

        $weekly = DB::table('job_batches')
            ->where('created_at', '>', now()->subWeek())
            ->whereNotNull('finished_at')
            ->sum('total_jobs');

        return [
            'hourly' => $hourly,
            'daily' => $daily,
            'weekly' => $weekly,
        ];
    }

    /**
     * Get performance statistics
     */
    private function getPerformanceStats(): array
    {
        $recentBatches = DB::table('job_batches')
            ->where('created_at', '>', now()->subDay())
            ->whereNotNull('finished_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(SECOND, created_at, finished_at)) as avg_duration')
            ->selectRaw('MAX(TIMESTAMPDIFF(SECOND, created_at, finished_at)) as max_duration')
            ->selectRaw('MIN(TIMESTAMPDIFF(SECOND, created_at, finished_at)) as min_duration')
            ->first();

        return [
            'avg_batch_duration' => $recentBatches->avg_duration ?? 0,
            'max_batch_duration' => $recentBatches->max_duration ?? 0,
            'min_batch_duration' => $recentBatches->min_duration ?? 0,
        ];
    }

    /**
     * Get recent job types
     */
    private function getRecentJobTypes(): array
    {
        return DB::table('jobs')
            ->where('created_at', '>', now()->subDay())
            ->pluck('payload')
            ->map(function ($payload) {
                $data = json_decode($payload, true);

                return $data['displayName'] ?? 'Unknown';
            })
            ->unique()
            ->toArray();
    }

    /**
     * Get jobs count by type and status
     */
    private function getJobsCountByTypeAndStatus(string $type, string $status): int
    {
        if ($status === 'pending') {
            return DB::table('jobs')
                ->where('payload', 'like', '%"displayName":"'.$type.'"%')
                ->count();
        }

        if ($status === 'failed') {
            return DB::table('failed_jobs')
                ->where('payload', 'like', '%"displayName":"'.$type.'"%')
                ->where('failed_at', '>', now()->subDay())
                ->count();
        }

        // For completed/processing, we need to track in our custom job tracking system
        return $this->getTrackedJobsCountByTypeAndStatus($type, $status);
    }

    /**
     * Get tracked jobs count by type and status
     */
    private function getTrackedJobsCountByTypeAndStatus(string $type, string $status): int
    {
        // This would query your custom job tracking table
        // For now, return estimated value
        return 0;
    }

    /**
     * Get average processing time for job type
     */
    private function getAverageProcessingTime(string $type): float
    {
        // This would query your custom job tracking table
        // For now, return estimated value
        return 0.0;
    }

    /**
     * Get success rate for job type
     */
    private function getSuccessRate(string $type): float
    {
        $total = $this->getJobsCountByTypeAndStatus($type, 'completed') +
                 $this->getJobsCountByTypeAndStatus($type, 'failed');

        if ($total === 0) {
            return 100.0;
        }

        $completed = $this->getJobsCountByTypeAndStatus($type, 'completed');

        return round(($completed / $total) * 100, 2);
    }

    /**
     * Get user jobs count
     */
    private function getUserJobsCount(int $userId): int
    {
        // This would query your custom job tracking table with user_id
        // For now, return estimated value
        return 0;
    }

    /**
     * Get user jobs count by status
     */
    private function getUserJobsCountByStatus(int $userId, string $status): int
    {
        // This would query your custom job tracking table
        // For now, return estimated value
        return 0;
    }

    /**
     * Get user recent jobs
     */
    private function getUserRecentJobs(int $userId, int $limit): array
    {
        // This would query your custom job tracking table
        // For now, return empty array
        return [];
    }

    /**
     * Get user job types
     */
    private function getUserJobTypes(int $userId): array
    {
        // This would query your custom job tracking table
        // For now, return empty array
        return [];
    }

    /**
     * Monitor job health
     */
    public function checkJobHealth(): array
    {
        $issues = [];

        // Check for high failed job count
        $failedCount = $this->getFailedJobsCount();
        if ($failedCount > 50) {
            $issues[] = [
                'severity' => 'high',
                'type' => 'failed_jobs',
                'message' => "High number of failed jobs: {$failedCount}",
                'recommendation' => 'Review failed jobs and fix underlying issues',
            ];
        }

        // Check for old pending jobs
        $oldPendingJobs = DB::table('jobs')
            ->where('created_at', '<', now()->subHours(2))
            ->count();

        if ($oldPendingJobs > 10) {
            $issues[] = [
                'severity' => 'medium',
                'type' => 'old_pending_jobs',
                'message' => "Old pending jobs detected: {$oldPendingJobs}",
                'recommendation' => 'Check queue workers and processing capacity',
            ];
        }

        // Check for stuck processing jobs
        $stuckJobs = DB::table('job_batches')
            ->where('created_at', '<', now()->subHours(1))
            ->whereNull('finished_at')
            ->where('pending_jobs', '>', 0)
            ->count();

        if ($stuckJobs > 5) {
            $issues[] = [
                'severity' => 'medium',
                'type' => 'stuck_jobs',
                'message' => "Potentially stuck processing jobs: {$stuckJobs}",
                'recommendation' => 'Monitor processing jobs and restart workers if needed',
            ];
        }

        return [
            'healthy' => empty($issues),
            'issues' => $issues,
            'recommendations' => $this->getHealthRecommendations($issues),
        ];
    }

    /**
     * Get health recommendations
     */
    private function getHealthRecommendations(array $issues): array
    {
        $recommendations = [];

        foreach ($issues as $issue) {
            switch ($issue['type']) {
                case 'failed_jobs':
                    $recommendations[] = 'Run: php artisan queue:failed-table to review failed jobs';
                    $recommendations[] = 'Consider implementing circuit breakers for failing services';
                    break;

                case 'old_pending_jobs':
                    $recommendations[] = 'Scale up queue workers';
                    $recommendations[] = 'Check for worker crashes or memory issues';
                    break;

                case 'stuck_jobs':
                    $recommendations[] = 'Monitor job processing times';
                    $recommendations[] = 'Consider implementing job timeouts';
                    break;
            }
        }

        return array_unique($recommendations);
    }

    /**
     * Clear monitoring cache
     */
    public function clearCache(): void
    {
        Cache::forget('queue_statistics');

        // Clear user-specific caches
        $users = DB::table('users')->pluck('id');
        foreach ($users as $userId) {
            Cache::forget("user_job_statistics_{$userId}");
        }
    }
}

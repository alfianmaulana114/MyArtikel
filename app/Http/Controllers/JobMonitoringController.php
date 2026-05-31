<?php

namespace App\Http\Controllers;

use App\Services\JobMonitoringService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class JobMonitoringController extends Controller
{
    private JobMonitoringService $monitoringService;

    public function __construct(JobMonitoringService $monitoringService)
    {
        $this->monitoringService = $monitoringService;
    }

    /**
     * Display job monitoring dashboard
     */
    public function index()
    {
        $queueStats = $this->monitoringService->getQueueStatistics();
        $jobTypeStats = $this->monitoringService->getJobTypeStatistics();
        $healthStatus = $this->monitoringService->checkJobHealth();

        return view('jobs.monitoring', [
            'queueStats' => $queueStats,
            'jobTypeStats' => $jobTypeStats,
            'healthStatus' => $healthStatus,
        ]);
    }

    /**
     * Get user-specific job statistics
     */
    public function userStats(Request $request)
    {
        $user = Auth::user();
        $stats = $this->monitoringService->getUserJobStatistics($user->id);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get queue statistics (API)
     */
    public function queueStats()
    {
        $stats = $this->monitoringService->getQueueStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Get job type statistics (API)
     */
    public function jobTypeStats()
    {
        $stats = $this->monitoringService->getJobTypeStatistics();

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Check job health status
     */
    public function health()
    {
        $health = $this->monitoringService->checkJobHealth();

        return response()->json([
            'success' => true,
            'data' => $health,
        ]);
    }

    /**
     * Retry failed jobs
     */
    public function retryFailed(Request $request)
    {
        $request->validate([
            'job_id' => 'nullable|integer',
            'all' => 'nullable|boolean',
        ]);

        try {
            if ($request->all) {
                // Retry all failed jobs
                Artisan::call('queue:retry', ['id' => 'all']);
                $message = 'All failed jobs have been queued for retry';
            } elseif ($request->job_id) {
                // Retry specific job
                Artisan::call('queue:retry', ['id' => $request->job_id]);
                $message = "Job {$request->job_id} has been queued for retry";
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Please specify a job ID or use the "all" parameter',
                ], 400);
            }

            $output = Artisan::output();

            return response()->json([
                'success' => true,
                'message' => $message,
                'output' => $output,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to retry jobs', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to retry jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete failed jobs
     */
    public function deleteFailed(Request $request)
    {
        $request->validate([
            'job_id' => 'nullable|integer',
            'all' => 'nullable|boolean',
        ]);

        try {
            if ($request->all) {
                // Delete all failed jobs
                Artisan::call('queue:flush');
                $message = 'All failed jobs have been deleted';
            } elseif ($request->job_id) {
                // Delete specific job
                $deleted = \DB::table('failed_jobs')->where('id', $request->job_id)->delete();
                $message = $deleted > 0
                    ? "Job {$request->job_id} has been deleted"
                    : "Job {$request->job_id} not found";
            } else {
                return response()->json([
                    'success' => false,
                    'error' => 'Please specify a job ID or use the "all" parameter',
                ], 400);
            }

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to delete jobs', [
                'error' => $e->getMessage(),
                'request' => $request->all(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to delete jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Restart queue workers
     */
    public function restartWorkers(Request $request)
    {
        try {
            // Send restart signal to workers
            Artisan::call('queue:restart');

            return response()->json([
                'success' => true,
                'message' => 'Queue workers restart signal sent. Workers will restart after finishing current jobs.',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to restart workers', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to restart workers: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clear monitoring cache
     */
    public function clearCache()
    {
        try {
            $this->monitoringService->clearCache();

            return response()->json([
                'success' => true,
                'message' => 'Monitoring cache cleared successfully',
            ]);

        } catch (Exception $e) {
            Log::error('Failed to clear monitoring cache', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to clear cache: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get failed jobs list
     */
    public function failedJobs(Request $request)
    {
        $request->validate([
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
        ]);

        $limit = $request->input('limit', 20);
        $offset = $request->input('offset', 0);

        try {
            $failedJobs = \DB::table('failed_jobs')
                ->orderBy('failed_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();

            $total = \DB::table('failed_jobs')->count();

            return response()->json([
                'success' => true,
                'data' => [
                    'jobs' => $failedJobs,
                    'total' => $total,
                    'limit' => $limit,
                    'offset' => $offset,
                ],
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get failed jobs', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get failed jobs: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get job details
     */
    public function jobDetails(Request $request, int $jobId)
    {
        try {
            $job = \DB::table('failed_jobs')
                ->where('id', $jobId)
                ->first();

            if (! $job) {
                return response()->json([
                    'success' => false,
                    'error' => 'Job not found',
                ], 404);
            }

            return response()->json([
                'success' => true,
                'data' => $job,
            ]);

        } catch (Exception $e) {
            Log::error('Failed to get job details', [
                'job_id' => $jobId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'error' => 'Failed to get job details: '.$e->getMessage(),
            ], 500);
        }
    }
}

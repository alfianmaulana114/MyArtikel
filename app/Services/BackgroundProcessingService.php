<?php

namespace App\Services;

use App\Jobs\ProcessArticleIngestion;
use App\Jobs\ProcessPdfIngestion;
use App\Jobs\ProcessSummaryGeneration;
use App\Jobs\ProcessPdfExport;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;
use Exception;

class BackgroundProcessingService
{
    private JobMonitoringService $monitoringService;
    private JobRetryService $retryService;
    
    public function __construct(
        JobMonitoringService $monitoringService,
        JobRetryService $retryService
    ) {
        $this->monitoringService = $monitoringService;
        $this->retryService = $retryService;
    }

    private function shouldForceSync(): bool
    {
        return app()->environment('local') && (bool) env('MYARTIKEL_FORCE_SYNC_JOBS', true);
    }

    /**
     * Process PDF ingestion in background
     */
    public function processPdfIngestion(int $userId, string $filePath, array $options = []): array
    {
        try {
            $job = new ProcessPdfIngestion($userId, $filePath, $options);

            if ($this->shouldForceSync()) {
                Bus::dispatchSync($job);
                $jobId = null;
                $queue = 'sync';
            } else {
                $jobId = dispatch($job->onQueue('low-priority'));
                $queue = 'low-priority';
            }

            Log::info('PDF ingestion job dispatched', [
                'user_id' => $userId,
                'file_path' => $filePath,
                'job_id' => $jobId,
                'queue' => $queue
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'message' => $queue === 'sync' ? 'PDF ingestion processed immediately' : 'PDF ingestion queued for processing',
                'queue' => $queue
            ];

        } catch (\Throwable $e) {
            Log::error('Failed to dispatch PDF ingestion job', [
                'user_id' => $userId,
                'file_path' => $filePath,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process article ingestion in background
     */
    public function processArticleIngestion(int $userId, string $url, array $options = []): array
    {
        try {
            // Validate input
            if (empty($url) || !filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL provided');
            }

            // Create job
            $job = new ProcessArticleIngestion($userId, $url, $options);

            if ($this->shouldForceSync()) {
                Bus::dispatchSync($job);
                $jobId = null;
                $queue = 'sync';
            } else {
                $jobId = dispatch($job->onQueue('low-priority'));
                $queue = 'low-priority';
            }
            
            Log::info('Article ingestion job dispatched', [
                'user_id' => $userId,
                'url' => $url,
                'job_id' => $jobId,
                'queue' => $queue
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'message' => $queue === 'sync' ? 'Article ingestion processed immediately' : 'Article ingestion queued for processing',
                'queue' => $queue
            ];

        } catch (Exception $e) {
            Log::error('Failed to dispatch article ingestion job', [
                'user_id' => $userId,
                'url' => $url,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process summary generation in background
     */
    public function processSummaryGeneration(int $articleId, int $userId, array $options = []): array
    {
        try {
            // Validate article exists
            $article = \App\Models\Article::find($articleId);
            if (!$article) {
                throw new Exception('Article not found');
            }

            // Check user permission
            if ($article->user_id !== $userId) {
                throw new Exception('Unauthorized access to article');
            }

            // Create summary record first
            $summary = \App\Models\Summary::create([
                'article_id' => $articleId,
                'user_id' => $userId,
                'content' => '',
                'word_count' => 0,
                'type' => 'ai_generated',
                'source' => 'pending',
                'status' => 'pending',
                'processing_started_at' => now()
            ]);

            // Create job
            $job = new ProcessSummaryGeneration($articleId, $userId, $summary->id, $options);

            if ($this->shouldForceSync()) {
                Bus::dispatchSync($job);
                $jobId = null;
                $queue = 'sync';
            } else {
                $jobId = dispatch($job->onQueue('summarization'));
                $queue = 'summarization';
            }
            
            Log::info('Summary generation job dispatched', [
                'article_id' => $articleId,
                'user_id' => $userId,
                'summary_id' => $summary->id,
                'job_id' => $jobId,
                'queue' => $queue
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'summary_id' => $summary->id,
                'message' => $queue === 'sync' ? 'Summary processed immediately' : 'Summary generation queued for processing',
                'queue' => $queue
            ];

        } catch (Exception $e) {
            Log::error('Failed to dispatch summary generation job', [
                'article_id' => $articleId,
                'user_id' => $userId,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process PDF export in background
     */
    public function processPdfExport(int $userId, array $articleIds, array $options = []): array
    {
        try {
            // Validate user
            $user = \App\Models\User::find($userId);
            if (!$user) {
                throw new Exception('User not found');
            }

            // Validate articles belong to user
            $validArticles = \App\Models\Article::where('user_id', $userId)
                ->whereIn('id', $articleIds)
                ->pluck('id')
                ->toArray();

            if (empty($validArticles)) {
                throw new Exception('No valid articles found for export');
            }

            // Create export record
            $export = \App\Models\Export::create([
                'user_id' => $userId,
                'type' => 'pdf',
                'file_path' => '',
                'file_size' => 0,
                'status' => 'pending',
                'metadata' => [
                    'article_ids' => $validArticles,
                    'export_options' => $options
                ],
                'processing_started_at' => now(),
                'expires_at' => now()->addDays(7)
            ]);

            // Create job
            $job = new ProcessPdfExport($userId, $validArticles, $options);

            if ($this->shouldForceSync()) {
                Bus::dispatchSync($job);
                $jobId = null;
                $queue = 'sync';
            } else {
                $jobId = dispatch($job->onQueue('exports'));
                $queue = 'exports';
            }
            
            Log::info('PDF export job dispatched', [
                'user_id' => $userId,
                'article_count' => count($validArticles),
                'export_id' => $export->id,
                'job_id' => $jobId,
                'queue' => $queue
            ]);

            return [
                'success' => true,
                'job_id' => $jobId,
                'export_id' => $export->id,
                'message' => $queue === 'sync' ? 'PDF export processed immediately' : 'PDF export queued for processing',
                'queue' => $queue
            ];

        } catch (Exception $e) {
            Log::error('Failed to dispatch PDF export job', [
                'user_id' => $userId,
                'article_ids' => $articleIds,
                'error' => $e->getMessage()
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Process batch operations
     */
    public function processBatchOperation(string $operation, array $items, array $options = []): array
    {
        try {
            $jobs = [];
            
            foreach ($items as $item) {
                switch ($operation) {
                    case 'summarize':
                        $job = new ProcessSummaryGeneration(
                            $item['article_id'],
                            $item['user_id'],
                            $item['summary_id'] ?? null,
                            $options
                        );
                        $jobs[] = $job->onQueue('summarization');
                        break;
                        
                    case 'export':
                        $job = new ProcessPdfExport(
                            $item['user_id'],
                            $item['article_ids'],
                            $options
                        );
                        $jobs[] = $job->onQueue('exports');
                        break;
                        
                    case 'ingest':
                        $job = new ProcessArticleIngestion(
                            $item['user_id'],
                            $item['url'],
                            $options
                        );
                        $jobs[] = $job->onQueue('low-priority');
                        break;
                        
                    default:
                        throw new Exception("Unknown batch operation: {$operation}");
                }
            }
            
            // Dispatch batch
            $batch = Bus::batch($jobs)
                ->then(function ($batch) use ($operation) {
                    Log::info("Batch operation completed", [
                        'operation' => $operation,
                        'batch_id' => $batch->id,
                        'total_jobs' => $batch->totalJobs,
                        'processed_jobs' => $batch->processedJobs()
                    ]);
                })
                ->catch(function ($batch, $exception) use ($operation) {
                    Log::error("Batch operation failed", [
                        'operation' => $operation,
                        'batch_id' => $batch->id,
                        'error' => $exception->getMessage()
                    ]);
                })
                ->dispatch();
            
            return [
                'success' => true,
                'batch_id' => $batch->id,
                'total_jobs' => count($jobs),
                'message' => 'Batch operation queued successfully'
            ];
            
        } catch (Exception $e) {
            Log::error('Batch operation failed', [
                'operation' => $operation,
                'items_count' => count($items),
                'error' => $e->getMessage()
            ]);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get job status
     */
    public function getJobStatus(string $jobId): array
    {
        try {
            // This would query the job tracking system
            // For now, return placeholder
            return [
                'success' => true,
                'job_id' => $jobId,
                'status' => 'processing',
                'progress' => 50,
                'message' => 'Job is being processed'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Cancel job
     */
    public function cancelJob(string $jobId): array
    {
        try {
            // This would implement job cancellation logic
            // For now, return placeholder
            return [
                'success' => true,
                'job_id' => $jobId,
                'message' => 'Job cancellation requested'
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Get queue statistics
     */
    public function getQueueStatistics(): array
    {
        return $this->monitoringService->getQueueStatistics();
    }

    /**
     * Optimize queue processing
     */
    public function optimizeProcessing(): array
    {
        try {
            $stats = $this->getQueueStatistics();
            $recommendations = [];
            
            // Check for high pending job count
            if ($stats['pending'] > 100) {
                $recommendations[] = 'Consider scaling up workers for high-priority queues';
            }
            
            // Check for high failed job count
            if ($stats['failed'] > 50) {
                $recommendations[] = 'Review and address failed jobs to improve success rate';
            }
            
            // Check performance metrics
            if ($stats['performance']['avg_batch_duration'] > 300) {
                $recommendations[] = 'Average processing time is high - consider optimizing job logic';
            }
            
            return [
                'success' => true,
                'statistics' => $stats,
                'recommendations' => $recommendations
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}

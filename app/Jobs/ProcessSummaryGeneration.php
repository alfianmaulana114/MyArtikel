<?php

namespace App\Jobs;

use App\Services\SummarizationService;
use Illuminate\Support\Facades\Log;

class ProcessSummaryGeneration extends BaseJob
{
    private int $articleId;
    private int $userId;
    private array $options;
    private int $summaryId;

    /**
     * Create a new job instance.
     */
    public function __construct(int $articleId, int $userId, int $summaryId, array $options = [])
    {
        $this->articleId = $articleId;
        $this->userId = $userId;
        $this->summaryId = $summaryId;
        $this->options = $options;
        
        // Set queue for summarization jobs
        $this->onQueue('summarization');
        
        $this->setJobMetadata([
            'article_id' => $articleId,
            'user_id' => $userId,
            'summary_id' => $summaryId,
            'options' => $options,
            'type' => 'summary_generation'
        ]);
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $summarizationService = app(SummarizationService::class);
        
        $result = $summarizationService->generateSummary(
            $this->articleId,
            $this->userId,
            $this->options
        );
        
        if (!$result['success']) {
            throw new \Exception($result['error']);
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        parent::failed($exception);
        
        // Update summary status to failed
        try {
            $summary = \App\Models\Summary::find($this->summaryId);
            if ($summary && in_array($summary->status, ['pending', 'processing'], true)) {
                $summary->update([
                    'status' => 'failed',
                    'error_message' => $exception->getMessage(),
                    'processing_completed_at' => now(),
                    'processing_time_ms' => $this->calculateProcessingTime($summary->processing_started_at)
                ]);
            }
        } catch (\Exception $updateError) {
            Log::error("Failed to update summary status: " . $updateError->getMessage());
        }
    }

    /**
     * Calculate processing time
     */
    private function calculateProcessingTime($startTime): int
    {
        if (!$startTime) {
            return 0;
        }
        return intval((microtime(true) - strtotime($startTime)) * 1000);
    }
}

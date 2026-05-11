<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Exception;

abstract class BaseJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 3;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 300; // 5 minutes

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var int
     */
    public $backoff = 60; // 1 minute

    /**
     * Job metadata for tracking and monitoring
     *
     * @var array
     */
    protected array $jobMetadata = [];

    /**
     * Job execution start time
     *
     * @var float
     */
    protected float $startTime;

    /**
     * Execute the job.
     *
     * @return void
     */
    abstract public function handle(): void;

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Job failed', [
            'job' => get_class($this),
            'queue' => $this->queue,
            'attempts' => $this->attempts(),
            'metadata' => $this->jobMetadata,
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]);

        // Record failure in job tracking if applicable
        $this->recordJobFailure($exception);
    }

    /**
     * Handle job processing.
     *
     * @return void
     */
    public function handleWithTracking(): void
    {
        $this->startTime = microtime(true);
        
        try {
            Log::info('Job started', [
                'job' => get_class($this),
                'queue' => $this->queue,
                'attempts' => $this->attempts(),
                'metadata' => $this->jobMetadata,
            ]);

            // Execute the main job logic
            $this->handle();

            $processingTime = microtime(true) - $this->startTime;
            
            Log::info('Job completed successfully', [
                'job' => get_class($this),
                'queue' => $this->queue,
                'processing_time' => round($processingTime, 2) . 's',
                'metadata' => $this->jobMetadata,
            ]);

            // Record success in job tracking if applicable
            $this->recordJobSuccess($processingTime);

        } catch (Exception $e) {
            $processingTime = microtime(true) - $this->startTime;
            
            Log::error('Job execution failed', [
                'job' => get_class($this),
                'queue' => $this->queue,
                'attempts' => $this->attempts(),
                'processing_time' => round($processingTime, 2) . 's',
                'metadata' => $this->jobMetadata,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Record job success in tracking system
     *
     * @param float $processingTime
     * @return void
     */
    protected function recordJobSuccess(float $processingTime): void
    {
        // Override in child classes if tracking is needed
    }

    /**
     * Record job failure in tracking system
     *
     * @param Exception $exception
     * @return void
     */
    protected function recordJobFailure(\Throwable $exception): void
    {
        // Override in child classes if tracking is needed
    }

    /**
     * Get job metadata
     *
     * @return array
     */
    public function getJobMetadata(): array
    {
        return $this->jobMetadata;
    }

    /**
     * Set job metadata
     *
     * @param array $metadata
     * @return $this
     */
    public function setJobMetadata(array $metadata): self
    {
        $this->jobMetadata = $metadata;
        return $this;
    }

    /**
     * Add metadata to job
     *
     * @param string $key
     * @param mixed $value
     * @return $this
     */
    public function addMetadata(string $key, $value): self
    {
        $this->jobMetadata[$key] = $value;
        return $this;
    }

    /**
     * Get current attempt number
     *
     * @return int
     */
    public function attempts(): int
    {
        return $this->job ? $this->job->attempts() : 0;
    }

    /**
     * Determine if the job should be retried.
     *
     * @param  \Exception  $exception
     * @return bool
     */
    public function shouldRetry(Exception $exception): bool
    {
        // Don't retry if it's a business logic exception
        if ($this->isBusinessLogicException($exception)) {
            return false;
        }

        // Retry on network/timeout exceptions
        if ($this->isRetryableException($exception)) {
            return true;
        }

        // Default retry logic
        return $this->attempts() < $this->tries;
    }

    /**
     * Check if exception is business logic related
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isBusinessLogicException(Exception $exception): bool
    {
        // Override in child classes to define business logic exceptions
        return false;
    }

    /**
     * Check if exception is retryable (network, timeout, etc.)
     *
     * @param Exception $exception
     * @return bool
     */
    protected function isRetryableException(Exception $exception): bool
    {
        $retryableExceptions = [
            \Illuminate\Http\Client\ConnectionException::class,
            \Illuminate\Http\Client\RequestException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \GuzzleHttp\Exception\ConnectException::class,
            \GuzzleHttp\Exception\RequestException::class,
        ];

        foreach ($retryableExceptions as $retryable) {
            if ($exception instanceof $retryable) {
                return true;
            }
        }

        // Check for specific error messages
        $retryableMessages = [
            'timeout',
            'connection',
            'network',
            'rate limit',
            'too many requests',
            'service unavailable',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($retryableMessages as $retryableMessage) {
            if (str_contains($message, $retryableMessage)) {
                return true;
            }
        }

        return false;
    }
}
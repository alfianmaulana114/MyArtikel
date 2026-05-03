<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Exception;

class JobRetryService
{
    private array $retryStrategies = [
        'exponential' => 'exponentialBackoff',
        'linear' => 'linearBackoff',
        'fixed' => 'fixedBackoff',
        'custom' => 'customBackoff',
    ];

    /**
     * Process failed job with retry logic
     */
    public function processFailedJob($job, Exception $exception): array
    {
        $jobClass = $this->getJobClass($job);
        $attempts = $this->getJobAttempts($job);
        $maxAttempts = $this->getMaxAttempts($jobClass);
        
        $retryDecision = $this->shouldRetry($jobClass, $exception, $attempts, $maxAttempts);
        
        if (!$retryDecision['should_retry']) {
            return [
                'action' => 'discard',
                'reason' => $retryDecision['reason'],
                'final_failure' => true
            ];
        }

        $retryDelay = $this->calculateRetryDelay($jobClass, $attempts, $exception);
        
        return [
            'action' => 'retry',
            'delay' => $retryDelay,
            'reason' => $retryDecision['reason'],
            'attempt' => $attempts + 1,
            'max_attempts' => $maxAttempts
        ];
    }

    /**
     * Determine if job should be retried
     */
    public function shouldRetry(string $jobClass, Exception $exception, int $attempts, int $maxAttempts): array
    {
        // Check if max attempts reached
        if ($attempts >= $maxAttempts) {
            return [
                'should_retry' => false,
                'reason' => 'Maximum attempts reached'
            ];
        }

        // Check if it's a business logic exception
        if ($this->isBusinessLogicException($jobClass, $exception)) {
            return [
                'should_retry' => false,
                'reason' => 'Business logic exception - no retry needed'
            ];
        }

        // Check if it's a retryable exception
        if ($this->isRetryableException($exception)) {
            return [
                'should_retry' => true,
                'reason' => 'Retryable exception type'
            ];
        }

        // Check for specific error patterns
        if ($this->hasRetryableErrorPattern($exception)) {
            return [
                'should_retry' => true,
                'reason' => 'Retryable error pattern detected'
            ];
        }

        // Default decision based on job class configuration
        if ($this->shouldRetryByDefault($jobClass)) {
            return [
                'should_retry' => true,
                'reason' => 'Default retry policy'
            ];
        }

        return [
            'should_retry' => false,
            'reason' => 'Exception not retryable'
        ];
    }

    /**
     * Calculate retry delay based on strategy
     */
    public function calculateRetryDelay(string $jobClass, int $attempts, Exception $exception): int
    {
        $strategy = $this->getRetryStrategy($jobClass, $exception);
        
        switch ($strategy) {
            case 'exponential':
                return $this->exponentialBackoff($attempts);
                
            case 'linear':
                return $this->linearBackoff($attempts);
                
            case 'fixed':
                return $this->fixedBackoff($attempts);
                
            case 'custom':
                return $this->customBackoff($jobClass, $attempts, $exception);
                
            default:
                return $this->exponentialBackoff($attempts);
        }
    }

    /**
     * Exponential backoff strategy
     */
    private function exponentialBackoff(int $attempts): int
    {
        // 1, 2, 4, 8, 16, 32, 64, 128, 256, 512 seconds
        return min(pow(2, $attempts - 1) * 60, 3600); // Max 1 hour
    }

    /**
     * Linear backoff strategy
     */
    private function linearBackoff(int $attempts): int
    {
        // 1, 2, 3, 4, 5, 6, 7, 8, 9, 10 minutes
        return min($attempts * 60, 3600); // Max 1 hour
    }

    /**
     * Fixed backoff strategy
     */
    private function fixedBackoff(int $attempts): int
    {
        return 300; // 5 minutes fixed
    }

    /**
     * Custom backoff strategy
     */
    private function customBackoff(string $jobClass, int $attempts, Exception $exception): int
    {
        // Get custom backoff configuration for specific job and exception
        $customConfig = $this->getCustomBackoffConfig($jobClass, $exception);
        
        if ($customConfig) {
            return $this->applyCustomBackoff($customConfig, $attempts);
        }
        
        return $this->exponentialBackoff($attempts);
    }

    /**
     * Check if exception is business logic related
     */
    private function isBusinessLogicException(string $jobClass, Exception $exception): bool
    {
        $businessExceptions = [
            \Illuminate\Validation\ValidationException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Illuminate\Database\Eloquent\ModelNotFoundException::class,
        ];

        foreach ($businessExceptions as $businessException) {
            if ($exception instanceof $businessException) {
                return true;
            }
        }

        // Check for specific business logic error messages
        $businessMessages = [
            'already exists',
            'not found',
            'invalid',
            'unauthorized',
            'forbidden',
            'validation failed',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($businessMessages as $businessMessage) {
            if (str_contains($message, $businessMessage)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if exception is retryable
     */
    private function isRetryableException(Exception $exception): bool
    {
        $retryableExceptions = [
            \Illuminate\Http\Client\ConnectionException::class,
            \Illuminate\Http\Client\RequestException::class,
            \GuzzleHttp\Exception\ConnectException::class,
            \GuzzleHttp\Exception\RequestException::class,
            \Illuminate\Database\QueryException::class,
            \PDOException::class,
        ];

        foreach ($retryableExceptions as $retryableException) {
            if ($exception instanceof $retryableException) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check for retryable error patterns
     */
    private function hasRetryableErrorPattern(Exception $exception): bool
    {
        $retryablePatterns = [
            'timeout',
            'connection',
            'network',
            'rate limit',
            'too many requests',
            'service unavailable',
            'gateway timeout',
            'connection refused',
            'operation timed out',
            'could not connect',
        ];

        $message = strtolower($exception->getMessage());
        foreach ($retryablePatterns as $pattern) {
            if (str_contains($message, $pattern)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get retry strategy for job class
     */
    private function getRetryStrategy(string $jobClass, Exception $exception): string
    {
        $jobConfig = $this->getJobConfiguration($jobClass);
        
        // Check for exception-specific strategy
        if (isset($jobConfig['retry_strategies'][$exception::class])) {
            return $jobConfig['retry_strategies'][$exception::class];
        }
        
        // Check for general strategy
        if (isset($jobConfig['retry_strategy'])) {
            return $jobConfig['retry_strategy'];
        }

        // Default strategy based on exception type
        if ($this->isRetryableException($exception)) {
            return 'exponential';
        }

        return 'exponential';
    }

    /**
     * Get job configuration
     */
    private function getJobConfiguration(string $jobClass): array
    {
        $configurations = [
            \App\Jobs\ProcessSummaryGeneration::class => [
                'retry_strategy' => 'exponential',
                'max_attempts' => 3,
                'retry_strategies' => [
                    \Illuminate\Http\Client\ConnectionException::class => 'exponential',
                    \GuzzleHttp\Exception\RequestException::class => 'linear',
                ]
            ],
            \App\Jobs\ProcessArticleIngestion::class => [
                'retry_strategy' => 'linear',
                'max_attempts' => 5,
                'retry_strategies' => [
                    \Illuminate\Http\Client\ConnectionException::class => 'exponential',
                    \GuzzleHttp\Exception\ConnectException::class => 'exponential',
                ]
            ],
            \App\Jobs\ProcessPdfExport::class => [
                'retry_strategy' => 'fixed',
                'max_attempts' => 2,
                'retry_strategies' => [
                    \Exception::class => 'fixed',
                ]
            ],
        ];

        return $configurations[$jobClass] ?? [
            'retry_strategy' => 'exponential',
            'max_attempts' => 3,
        ];
    }

    /**
     * Get max attempts for job class
     */
    private function getMaxAttempts(string $jobClass): int
    {
        $config = $this->getJobConfiguration($jobClass);
        return $config['max_attempts'] ?? 3;
    }

    /**
     * Check if job should retry by default
     */
    private function shouldRetryByDefault(string $jobClass): bool
    {
        $config = $this->getJobConfiguration($jobClass);
        return $config['retry_by_default'] ?? true;
    }

    /**
     * Get job class from job instance
     */
    private function getJobClass($job): string
    {
        if (is_object($job)) {
            return get_class($job);
        }

        if (is_string($job)) {
            return $job;
        }

        return 'Unknown';
    }

    /**
     * Get job attempts from job instance
     */
    private function getJobAttempts($job): int
    {
        if (method_exists($job, 'attempts')) {
            return $job->attempts();
        }

        if (property_exists($job, 'attempts')) {
            return $job->attempts;
        }

        return 0;
    }

    /**
     * Get custom backoff configuration
     */
    private function getCustomBackoffConfig(string $jobClass, Exception $exception): ?array
    {
        $config = $this->getJobConfiguration($jobClass);
        return $config['custom_backoff'][$exception::class] ?? null;
    }

    /**
     * Apply custom backoff configuration
     */
    private function applyCustomBackoff(array $config, int $attempts): int
    {
        if (isset($config['delays'][$attempts - 1])) {
            return $config['delays'][$attempts - 1];
        }

        if (isset($config['formula'])) {
            // Evaluate custom formula
            // This is a placeholder - implement based on your needs
            return eval($config['formula']);
        }

        return $this->exponentialBackoff($attempts);
    }

    /**
     * Log retry decision
     */
    public function logRetryDecision(array $decision, string $jobClass, Exception $exception): void
    {
        Log::info('Job retry decision', [
            'job_class' => $jobClass,
            'action' => $decision['action'],
            'reason' => $decision['reason'],
            'delay' => $decision['delay'] ?? null,
            'attempt' => $decision['attempt'] ?? null,
            'max_attempts' => $decision['max_attempts'] ?? null,
            'exception' => get_class($exception),
            'exception_message' => $exception->getMessage(),
        ]);
    }
}
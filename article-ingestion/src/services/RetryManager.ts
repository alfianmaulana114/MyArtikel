import { IngestionResult, IngestionError, IngestionErrorType } from "../types";

/**
 * Retry mechanism untuk failed ingestion attempts
 */
export class RetryManager {
    private readonly maxRetries: number;
    private readonly baseDelay: number;
    private readonly maxDelay: number;
    private readonly backoffMultiplier: number;

    constructor(
        maxRetries: number = 3,
        baseDelay: number = 1000, // 1 second
        maxDelay: number = 30000, // 30 seconds
        backoffMultiplier: number = 2,
    ) {
        this.maxRetries = maxRetries;
        this.baseDelay = baseDelay;
        this.maxDelay = maxDelay;
        this.backoffMultiplier = backoffMultiplier;
    }

    /**
     * Determine if job should be retried
     */
    shouldRetry(result: IngestionResult): boolean {
        if (result.status !== "failed") {
            return false;
        }

        if (!result.error?.retryable) {
            return false;
        }

        if (result.attempts >= this.maxRetries) {
            return false;
        }

        return true;
    }

    /**
     * Calculate retry delay dengan exponential backoff
     */
    getRetryDelay(attemptNumber: number): number {
        const delay =
            this.baseDelay *
            Math.pow(this.backoffMultiplier, attemptNumber - 1);
        return Math.min(delay, this.maxDelay);
    }

    /**
     * Get retryable jobs dari list results
     */
    getRetryableJobs(results: IngestionResult[]): IngestionResult[] {
        return results.filter((result) => this.shouldRetry(result));
    }

    /**
     * Schedule retry untuk job
     */
    async scheduleRetry(
        result: IngestionResult,
        retryFunction: (result: IngestionResult) => Promise<void>,
    ): Promise<void> {
        if (!this.shouldRetry(result)) {
            return;
        }

        const delay = this.getRetryDelay(result.attempts);

        console.log(
            `Scheduling retry for job ${result.id} in ${delay}ms (attempt ${result.attempts + 1}/${this.maxRetries})`,
        );

        setTimeout(async () => {
            try {
                await retryFunction(result);
            } catch (error) {
                console.error(`Retry failed for job ${result.id}:`, error);
            }
        }, delay);
    }

    /**
     * Get retry statistics
     */
    getRetryStats(results: IngestionResult[]): {
        totalRetryable: number;
        maxRetriesReached: number;
        nonRetryableFailures: number;
        averageAttempts: number;
    } {
        const failedJobs = results.filter((r) => r.status === "failed");
        const retryableJobs = failedJobs.filter((r) => this.shouldRetry(r));
        const maxRetriesReached = failedJobs.filter(
            (r) => r.attempts >= this.maxRetries,
        );
        const nonRetryableFailures = failedJobs.filter(
            (r) => !r.error?.retryable,
        );

        const totalAttempts = failedJobs.reduce(
            (sum, job) => sum + job.attempts,
            0,
        );
        const averageAttempts =
            failedJobs.length > 0 ? totalAttempts / failedJobs.length : 0;

        return {
            totalRetryable: retryableJobs.length,
            maxRetriesReached: maxRetriesReached.length,
            nonRetryableFailures: nonRetryableFailures.length,
            averageAttempts: Math.round(averageAttempts * 100) / 100,
        };
    }
}

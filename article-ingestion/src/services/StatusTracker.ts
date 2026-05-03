import { IngestionStatus, IngestionResult, IngestionError } from "../types";

/**
 * State machine untuk article ingestion status tracking
 */
export class StatusTracker {
    private readonly results: Map<string, IngestionResult> = new Map();

    /**
     * Create new ingestion job
     */
    createJob(url: string): IngestionResult {
        const id = this.generateId();
        const result: IngestionResult = {
            id,
            url,
            status: IngestionStatus.QUEUED,
            attempts: 0,
            createdAt: new Date(),
            updatedAt: new Date(),
        };

        this.results.set(id, result);
        return result;
    }

    /**
     * Update job status
     */
    updateStatus(
        id: string,
        status: IngestionStatus,
        metadata?: any,
    ): IngestionResult {
        const result = this.results.get(id);
        if (!result) {
            throw new Error(`Job not found: ${id}`);
        }

        // Validasi state transitions
        if (!this.isValidTransition(result.status, status)) {
            throw new Error(
                `Invalid status transition: ${result.status} -> ${status}`,
            );
        }

        // Update result
        result.status = status;
        result.updatedAt = new Date();

        if (metadata) {
            Object.assign(result, metadata);
        }

        // Update attempts untuk failed status
        if (status === IngestionStatus.FAILED) {
            result.attempts++;
        }

        // Set completed at untuk terminal states
        if (this.isTerminalState(status)) {
            result.completedAt = new Date();
        }

        this.results.set(id, result);
        return result;
    }

    /**
     * Mark job as failed
     */
    markFailed(id: string, error: IngestionError): IngestionResult {
        return this.updateStatus(id, IngestionStatus.FAILED, { error });
    }

    /**
     * Mark job as ready dengan content
     */
    markReady(id: string, metadata: any, content: any): IngestionResult {
        return this.updateStatus(id, IngestionStatus.READY, {
            metadata,
            content,
        });
    }

    /**
     * Get job by ID
     */
    getJob(id: string): IngestionResult | undefined {
        return this.results.get(id);
    }

    /**
     * Get all jobs
     */
    getAllJobs(): IngestionResult[] {
        return Array.from(this.results.values());
    }

    /**
     * Get jobs by status
     */
    getJobsByStatus(status: IngestionStatus): IngestionResult[] {
        return Array.from(this.results.values()).filter(
            (job) => job.status === status,
        );
    }

    /**
     * Get jobs yang perlu diretry
     */
    getRetryableJobs(): IngestionResult[] {
        return Array.from(this.results.values()).filter(
            (job) =>
                job.status === IngestionStatus.FAILED &&
                job.error?.retryable &&
                job.attempts < 3, // Max 3 attempts
        );
    }

    /**
     * Delete completed jobs (cleanup)
     */
    cleanupCompletedJobs(olderThanHours: number = 24): number {
        const cutoffTime = new Date(
            Date.now() - olderThanHours * 60 * 60 * 1000,
        );
        let deletedCount = 0;

        for (const [id, result] of this.results.entries()) {
            if (
                this.isTerminalState(result.status) &&
                result.completedAt &&
                result.completedAt < cutoffTime
            ) {
                this.results.delete(id);
                deletedCount++;
            }
        }

        return deletedCount;
    }

    /**
     * Get statistics
     */
    getStats(): {
        total: number;
        queued: number;
        fetching: number;
        extracting: number;
        ready: number;
        failed: number;
        successRate: number;
        averageProcessingTime: number;
    } {
        const jobs = Array.from(this.results.values());
        const stats = {
            total: jobs.length,
            queued: 0,
            fetching: 0,
            extracting: 0,
            ready: 0,
            failed: 0,
            successRate: 0,
            averageProcessingTime: 0,
        };

        let totalProcessingTime = 0;
        let completedJobs = 0;

        jobs.forEach((job) => {
            switch (job.status) {
                case IngestionStatus.QUEUED:
                    stats.queued++;
                    break;
                case IngestionStatus.FETCHING:
                    stats.fetching++;
                    break;
                case IngestionStatus.EXTRACTING:
                    stats.extracting++;
                    break;
                case IngestionStatus.READY:
                    stats.ready++;
                    break;
                case IngestionStatus.FAILED:
                    stats.failed++;
                    break;
            }

            if (job.completedAt && job.createdAt) {
                totalProcessingTime +=
                    job.completedAt.getTime() - job.createdAt.getTime();
                completedJobs++;
            }
        });

        if (completedJobs > 0) {
            stats.averageProcessingTime = Math.round(
                totalProcessingTime / completedJobs / 1000,
            ); // in seconds
        }

        if (stats.total > 0) {
            stats.successRate = Math.round(
                (stats.ready / (stats.ready + stats.failed)) * 100,
            );
        }

        return stats;
    }

    /**
     * Validasi state transitions
     */
    private isValidTransition(
        from: IngestionStatus,
        to: IngestionStatus,
    ): boolean {
        const validTransitions: Record<IngestionStatus, IngestionStatus[]> = {
            [IngestionStatus.QUEUED]: [
                IngestionStatus.FETCHING,
                IngestionStatus.FAILED,
            ],
            [IngestionStatus.FETCHING]: [
                IngestionStatus.EXTRACTING,
                IngestionStatus.FAILED,
            ],
            [IngestionStatus.EXTRACTING]: [
                IngestionStatus.READY,
                IngestionStatus.FAILED,
            ],
            [IngestionStatus.READY]: [], // Terminal state
            [IngestionStatus.FAILED]: [
                IngestionStatus.FETCHING, // Untuk retry
            ],
        };

        return validTransitions[from]?.includes(to) ?? false;
    }

    /**
     * Cek apakah status adalah terminal state
     */
    private isTerminalState(status: IngestionStatus): boolean {
        return (
            status === IngestionStatus.READY ||
            status === IngestionStatus.FAILED
        );
    }

    /**
     * Generate unique ID untuk job
     */
    private generateId(): string {
        return `job_${Date.now()}_${Math.random().toString(36).substr(2, 9)}`;
    }
}

/**
 * In-memory storage untuk status tracking (bisa diganti dengan database)
 */
export class InMemoryStatusStore {
    private store: Map<string, IngestionResult> = new Map();

    save(result: IngestionResult): void {
        this.store.set(result.id, result);
    }

    get(id: string): IngestionResult | undefined {
        return this.store.get(id);
    }

    getAll(): IngestionResult[] {
        return Array.from(this.store.values());
    }

    delete(id: string): boolean {
        return this.store.delete(id);
    }

    clear(): void {
        this.store.clear();
    }

    size(): number {
        return this.store.size;
    }
}

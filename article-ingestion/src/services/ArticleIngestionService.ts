import { v4 as uuidv4 } from "uuid";
import {
    IngestionResult,
    IngestionStatus,
    IngestionConfig,
    IngestionError,
    IngestionErrorType,
    ArticleContent,
    ArticleMetadata,
} from "../types";
import { UrlValidator } from "./UrlValidator";
import { SafeHttpClient } from "./SafeHttpClient";
import { ContentExtractor } from "./ContentExtractor";
import { HtmlSanitizer } from "./HtmlSanitizer";
import { StatusTracker } from "./StatusTracker";
import { RetryManager } from "./RetryManager";
import pino from "pino";

/**
 * Main Article Ingestion Service dengan comprehensive error handling
 */
export class ArticleIngestionService {
    private readonly config: IngestionConfig;
    private readonly urlValidator: UrlValidator;
    private readonly httpClient: SafeHttpClient;
    private readonly contentExtractor: ContentExtractor;
    private readonly htmlSanitizer: HtmlSanitizer;
    private readonly statusTracker: StatusTracker;
    private readonly retryManager: RetryManager;
    private readonly logger: pino.Logger;

    constructor(config: Partial<IngestionConfig> = {}) {
        this.config = {
            maxContentSize: 5 * 1024 * 1024, // 5MB
            maxRedirects: 5,
            requestTimeout: 30000, // 30 seconds
            maxRetries: 3,
            retryDelay: 1000,
            userAgent: "ArticleIngestionBot/1.0 (https://yourapp.com/bot)",
            allowedSchemes: ["http:", "https:"],
            blockedHosts: [
                "localhost",
                "127.0.0.1",
                "0.0.0.0",
                "10.0.0.0",
                "172.16.0.0",
                "192.168.0.0",
            ],
            privateIpRanges: [
                "^127\\.",
                "^10\\.",
                "^172\\.(1[6-9]|2[0-9]|3[01])\\.",
                "^192\\.168\\.",
                "^169\\.254\\.",
                "^::1$",
                "^fc00:",
                "^fe80:",
            ],
            sanitizeHtml: true,
            extractMetadata: true,
            ...config,
        };

        // Initialize services
        this.urlValidator = new UrlValidator(
            this.config.blockedHosts,
            this.config.privateIpRanges,
            this.config.allowedSchemes,
        );

        this.httpClient = new SafeHttpClient(this.urlValidator, {
            maxContentSize: this.config.maxContentSize,
            maxRedirects: this.config.maxRedirects,
            requestTimeout: this.config.requestTimeout,
            userAgent: this.config.userAgent,
        });

        this.contentExtractor = new ContentExtractor();
        this.htmlSanitizer = new HtmlSanitizer();
        this.statusTracker = new StatusTracker();
        this.retryManager = new RetryManager(
            this.config.maxRetries,
            this.config.retryDelay,
        );

        this.logger = pino({
            level: "info",
            transport: {
                target: "pino-pretty",
                options: {
                    colorize: true,
                    translateTime: "SYS:standard",
                    ignore: "pid,hostname",
                },
            },
        });
    }

    /**
     * Main ingestion method
     */
    async ingest(url: string): Promise<IngestionResult> {
        const jobId = uuidv4();
        this.logger.info({ url, jobId }, "Starting article ingestion");

        try {
            // Create job
            let result = this.statusTracker.createJob(url);
            result.id = jobId;

            // Step 1: URL Validation
            await this.updateStatus(jobId, IngestionStatus.FETCHING);
            const validation =
                await this.urlValidator.validateAndNormalize(url);
            if (!validation.isValid) {
                throw this.createError(
                    IngestionErrorType.INVALID_URL,
                    `URL validation failed: ${validation.error}`,
                    false,
                );
            }

            // Step 2: HTTP Fetch dengan SSRF protection
            this.logger.info(
                { url: validation.normalizedUrl, jobId },
                "Fetching content",
            );
            const httpResponse = await this.httpClient.fetch(
                validation.normalizedUrl,
            );

            // Step 3: Content Extraction
            await this.updateStatus(jobId, IngestionStatus.EXTRACTING);
            this.logger.info({ jobId }, "Extracting content");

            let htmlContent = httpResponse.content;

            // Sanitize HTML jika diaktifkan
            if (this.config.sanitizeHtml) {
                this.logger.info({ jobId }, "Sanitizing HTML");
                htmlContent = this.htmlSanitizer.sanitize(htmlContent);
            }

            // Extract content dan metadata
            const articleContent = this.contentExtractor.extractContent(
                htmlContent,
                validation.normalizedUrl,
            );
            const metadata = this.contentExtractor.extractMetadata(
                this.loadHTML(htmlContent),
                validation.normalizedUrl,
            );

            // Step 4: Complete
            await this.updateStatus(jobId, IngestionStatus.READY, {
                metadata,
                content: articleContent,
            });

            this.logger.info(
                {
                    jobId,
                    url: validation.normalizedUrl,
                    title: articleContent.title,
                    wordCount: articleContent.wordCount,
                },
                "Article ingestion completed successfully",
            );

            return this.statusTracker.getJob(jobId)!;
        } catch (error) {
            return await this.handleError(jobId, error);
        }
    }

    /**
     * Batch ingestion untuk multiple URLs
     */
    async ingestBatch(urls: string[]): Promise<IngestionResult[]> {
        this.logger.info({ count: urls.length }, "Starting batch ingestion");

        const results: IngestionResult[] = [];

        // Process URLs dengan rate limiting
        for (const url of urls) {
            try {
                const result = await this.ingest(url);
                results.push(result);

                // Delay antara requests untuk avoid rate limiting
                await this.delay(1000);
            } catch (error) {
                this.logger.error(
                    { url, error },
                    "Batch ingestion failed for URL",
                );

                // Create failed result
                const failedResult: IngestionResult = {
                    id: uuidv4(),
                    url,
                    status: IngestionStatus.FAILED,
                    error:
                        error instanceof Error
                            ? this.createErrorFromException(error)
                            : {
                                  type: IngestionErrorType.UNKNOWN_ERROR,
                                  message: "Unknown error",
                                  retryable: false,
                                  timestamp: new Date(),
                              },
                    attempts: 1,
                    createdAt: new Date(),
                    updatedAt: new Date(),
                    completedAt: new Date(),
                };

                results.push(failedResult);
            }
        }

        this.logger.info(
            {
                total: results.length,
                successful: results.filter(
                    (r) => r.status === IngestionStatus.READY,
                ).length,
                failed: results.filter(
                    (r) => r.status === IngestionStatus.FAILED,
                ).length,
            },
            "Batch ingestion completed",
        );

        return results;
    }

    /**
     * Get job status
     */
    getJobStatus(jobId: string): IngestionResult | undefined {
        return this.statusTracker.getJob(jobId);
    }

    /**
     * Get all jobs
     */
    getAllJobs(): IngestionResult[] {
        return this.statusTracker.getAllJobs();
    }

    /**
     * Get jobs by status
     */
    getJobsByStatus(status: IngestionStatus): IngestionResult[] {
        return this.statusTracker.getJobsByStatus(status);
    }

    /**
     * Get statistics
     */
    getStats() {
        return this.statusTracker.getStats();
    }

    /**
     * Process retry queue
     */
    async processRetries(): Promise<void> {
        const retryableJobs = this.statusTracker.getRetryableJobs();

        if (retryableJobs.length === 0) {
            return;
        }

        this.logger.info(
            { count: retryableJobs.length },
            "Processing retry queue",
        );

        for (const job of retryableJobs) {
            try {
                await this.retryManager.scheduleRetry(job, async (retryJob) => {
                    this.logger.info(
                        { jobId: retryJob.id, attempt: retryJob.attempts + 1 },
                        "Retrying job",
                    );

                    // Reset status untuk retry
                    await this.updateStatus(
                        retryJob.id,
                        IngestionStatus.QUEUED,
                    );

                    // Retry ingestion
                    await this.ingest(retryJob.url);
                });
            } catch (error) {
                this.logger.error(
                    { jobId: job.id, error },
                    "Failed to schedule retry",
                );
            }
        }
    }

    /**
     * Cleanup old completed jobs
     */
    cleanup(olderThanHours: number = 24): number {
        const deleted = this.statusTracker.cleanupCompletedJobs(olderThanHours);
        this.logger.info({ deleted, olderThanHours }, "Cleanup completed");
        return deleted;
    }

    /**
     * Update job status
     */
    private async updateStatus(
        jobId: string,
        status: IngestionStatus,
        metadata?: any,
    ): Promise<void> {
        try {
            this.statusTracker.updateStatus(jobId, status, metadata);
        } catch (error) {
            this.logger.error(
                { jobId, status, error },
                "Failed to update job status",
            );
            throw error;
        }
    }

    /**
     * Handle errors dengan proper classification
     */
    private async handleError(
        jobId: string,
        error: any,
    ): Promise<IngestionResult> {
        let ingestionError: IngestionError;

        if (error instanceof Error && "type" in error) {
            ingestionError = error as IngestionError;
        } else if (error instanceof Error) {
            ingestionError = this.createErrorFromException(error);
        } else {
            ingestionError = {
                type: IngestionErrorType.UNKNOWN_ERROR,
                message: "Unknown error occurred",
                retryable: false,
                timestamp: new Date(),
            };
        }

        this.logger.error(
            {
                jobId,
                errorType: ingestionError.type,
                message: ingestionError.message,
            },
            "Article ingestion failed",
        );

        await this.updateStatus(jobId, IngestionStatus.FAILED, {
            error: ingestionError,
        });
        return this.statusTracker.getJob(jobId)!;
    }

    /**
     * Create error dari exception
     */
    private createErrorFromException(error: Error): IngestionError {
        let type = IngestionErrorType.UNKNOWN_ERROR;
        let retryable = false;

        // Classify error berdasarkan message/pattern
        const message = error.message.toLowerCase();

        if (message.includes("timeout")) {
            type = IngestionErrorType.TIMEOUT;
            retryable = true;
        } else if (
            message.includes("network") ||
            message.includes("connection")
        ) {
            type = IngestionErrorType.NETWORK_ERROR;
            retryable = true;
        } else if (message.includes("dns") || message.includes("resolve")) {
            type = IngestionErrorType.NETWORK_ERROR;
            retryable = true;
        } else if (message.includes("invalid url")) {
            type = IngestionErrorType.INVALID_URL;
            retryable = false;
        } else if (message.includes("content too large")) {
            type = IngestionErrorType.CONTENT_TOO_LARGE;
            retryable = false;
        } else if (message.includes("ssrf")) {
            type = IngestionErrorType.SSRF_BLOCKED;
            retryable = false;
        } else if (message.includes("extraction")) {
            type = IngestionErrorType.EXTRACTION_FAILED;
            retryable = false;
        } else if (message.includes("sanitization")) {
            type = IngestionErrorType.SANITIZATION_FAILED;
            retryable = false;
        }

        return {
            type,
            message: error.message,
            retryable,
            timestamp: new Date(),
        };
    }

    /**
     * Create error dengan proper typing
     */
    private createError(
        type: IngestionErrorType,
        message: string,
        retryable: boolean,
    ): IngestionError {
        return {
            type,
            message,
            retryable,
            timestamp: new Date(),
        };
    }

    /**
     * Load HTML untuk content extraction
     */
    private loadHTML(html: string): any {
        // This is a placeholder - ContentExtractor should handle this
        return html;
    }

    /**
     * Delay utility
     */
    private delay(ms: number): Promise<void> {
        return new Promise((resolve) => setTimeout(resolve, ms));
    }
}

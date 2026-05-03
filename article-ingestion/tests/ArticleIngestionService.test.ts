import { ArticleIngestionService } from "../src/services/ArticleIngestionService";
import { IngestionStatus, IngestionErrorType } from "../src/types";

describe("ArticleIngestionService", () => {
    let service: ArticleIngestionService;

    beforeEach(() => {
        service = new ArticleIngestionService({
            maxContentSize: 1024 * 1024, // 1MB untuk testing
            requestTimeout: 5000,
            maxRetries: 2,
        });
    });

    describe("URL Validation", () => {
        it("should reject invalid URLs", async () => {
            const result = await service.ingest("not-a-valid-url");

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.type).toBe(IngestionErrorType.INVALID_URL);
        });

        it("should reject private IP addresses", async () => {
            const result = await service.ingest("http://192.168.1.1");

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.type).toBe(IngestionErrorType.INVALID_URL);
        });

        it("should reject localhost", async () => {
            const result = await service.ingest("http://localhost:8080");

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.type).toBe(IngestionErrorType.INVALID_URL);
        });

        it("should normalize URLs dan remove tracking parameters", async () => {
            const result = await service.ingest(
                "https://example.com/article?utm_source=test&fbclid=abc",
            );

            // This will fail karena network tapi URL should be normalized
            expect(result.url).toBe("https://example.com/article");
        });
    });

    describe("Status Tracking", () => {
        it("should create job dengan proper status tracking", async () => {
            const result = await service.ingest("https://httpbin.org/html");

            expect(result.id).toBeDefined();
            expect(result.status).toBeDefined();
            expect(result.createdAt).toBeDefined();
            expect(result.updatedAt).toBeDefined();
        });

        it("should track job status properly", async () => {
            const result = await service.ingest("https://httpbin.org/html");

            const trackedResult = service.getJobStatus(result.id);
            expect(trackedResult).toBeDefined();
            expect(trackedResult?.id).toBe(result.id);
        });
    });

    describe("Error Handling", () => {
        it("should handle network errors gracefully", async () => {
            const result = await service.ingest(
                "https://nonexistent-domain-12345.com",
            );

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error).toBeDefined();
            expect(result.error?.retryable).toBe(true); // Network errors should be retryable
        });

        it("should handle timeout errors", async () => {
            const result = await service.ingest("https://httpbin.org/delay/10"); // This will timeout

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.type).toBe(IngestionErrorType.TIMEOUT);
        });

        it("should handle content too large", async () => {
            // Test dengan URL yang return content > maxContentSize
            const result = await service.ingest(
                "https://httpbin.org/bytes/2000000",
            ); // 2MB > 1MB limit

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.type).toBe(
                IngestionErrorType.CONTENT_TOO_LARGE,
            );
        });
    });

    describe("Batch Processing", () => {
        it("should process multiple URLs", async () => {
            const urls = [
                "https://httpbin.org/html",
                "https://httpbin.org/json",
                "https://httpbin.org/xml",
            ];

            const results = await service.ingestBatch(urls);

            expect(results).toHaveLength(3);
            results.forEach((result) => {
                expect(result.id).toBeDefined();
                expect(result.status).toBeDefined();
            });
        });
    });

    describe("Statistics", () => {
        it("should provide accurate statistics", async () => {
            // Process beberapa jobs
            await service.ingest("https://httpbin.org/html");
            await service.ingest("https://nonexistent-domain.com"); // This will fail

            const stats = service.getStats();

            expect(stats.total).toBeGreaterThanOrEqual(2);
            expect(stats.successRate).toBeDefined();
            expect(stats.averageProcessingTime).toBeGreaterThanOrEqual(0);
        });
    });

    describe("Retry Mechanism", () => {
        it("should handle retryable errors properly", async () => {
            const result = await service.ingest(
                "https://nonexistent-domain.com",
            );

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.retryable).toBe(true);
            expect(result.attempts).toBe(1);
        });

        it("should not retry non-retryable errors", async () => {
            const result = await service.ingest("invalid-url-format");

            expect(result.status).toBe(IngestionStatus.FAILED);
            expect(result.error?.retryable).toBe(false);
        });
    });
});

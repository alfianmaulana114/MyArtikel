import { ArticleIngestionService } from "./src/services/ArticleIngestionService";

/**
 * Example usage of Article Ingestion System
 */
async function main() {
    // Initialize the service dengan custom configuration
    const service = new ArticleIngestionService({
        maxContentSize: 5 * 1024 * 1024, // 5MB
        maxRedirects: 5,
        requestTimeout: 30000, // 30 seconds
        maxRetries: 3,
        retryDelay: 1000,
        userAgent: "ArticleIngestionBot/1.0",
        sanitizeHtml: true,
        extractMetadata: true,
    });

    try {
        console.log("🚀 Starting article ingestion...");

        // Single URL ingestion
        const url = "https://example.com/article";
        console.log(`📄 Processing: ${url}`);

        const result = await service.ingest(url);

        if (result.status === "ready") {
            console.log("✅ Article ingested successfully!");
            console.log(`📖 Title: ${result.content?.title}`);
            console.log(`✍️  Author: ${result.content?.author || "Unknown"}`);
            console.log(`📝 Word Count: ${result.content?.wordCount}`);
            console.log(
                `⏱️  Reading Time: ${result.content?.readingTime} minutes`,
            );
            console.log(`📅 Published: ${result.content?.publishedDate}`);
            console.log(`
📝 Content Preview:
${result.content?.excerpt}
`);
        } else if (result.status === "failed") {
            console.log("❌ Article ingestion failed!");
            console.log(`🔴 Error: ${result.error?.message}`);
            console.log(`🔄 Retryable: ${result.error?.retryable}`);
        }

        // Batch processing example
        console.log("\n📊 Processing batch of URLs...");
        const batchUrls = [
            "https://example.com/article1",
            "https://example.com/article2",
            "https://example.com/article3",
        ];

        const batchResults = await service.ingestBatch(batchUrls);

        console.log(`\n📈 Batch Results:`);
        const successful = batchResults.filter(
            (r) => r.status === "ready",
        ).length;
        const failed = batchResults.filter((r) => r.status === "failed").length;

        console.log(`✅ Successful: ${successful}`);
        console.log(`❌ Failed: ${failed}`);

        // Display statistics
        const stats = service.getStats();
        console.log(`\n📊 System Statistics:`);
        console.log(`Total Jobs: ${stats.total}`);
        console.log(`Success Rate: ${stats.successRate}%`);
        console.log(`Average Processing Time: ${stats.averageProcessingTime}s`);

        // Process retries untuk failed jobs
        console.log("\n🔄 Processing retry queue...");
        await service.processRetries();
    } catch (error) {
        console.error("💥 Unexpected error:", error);
    }
}

// Error handling example
async function handleErrors() {
    const service = new ArticleIngestionService();

    // Test dengan invalid URL
    try {
        const result = await service.ingest("invalid-url");
        if (result.status === "failed") {
            console.log(`Error Type: ${result.error?.type}`);
            console.log(`Error Message: ${result.error?.message}`);
            console.log(`Retryable: ${result.error?.retryable}`);
        }
    } catch (error) {
        console.error("Error handling failed:", error);
    }

    // Test dengan private IP (SSRF protection)
    try {
        const result = await service.ingest("http://192.168.1.1");
        if (result.status === "failed") {
            console.log(`SSRF Blocked: ${result.error?.message}`);
        }
    } catch (error) {
        console.error("SSRF protection failed:", error);
    }
}

// Advanced configuration example
function advancedConfiguration() {
    const service = new ArticleIngestionService({
        // Security settings
        maxContentSize: 10 * 1024 * 1024, // 10MB max
        maxRedirects: 10,
        requestTimeout: 60000, // 1 minute

        // Retry settings
        maxRetries: 5,
        retryDelay: 2000, // 2 seconds base delay

        // User agent
        userAgent: "MyApp/1.0 (https://myapp.com/bot)",

        // Allowed schemes
        allowedSchemes: ["http:", "https:"],

        // Blocked hosts untuk additional security
        blockedHosts: ["malicious-site.com", "another-bad-site.com"],

        // Private IP ranges untuk SSRF protection
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

        // Content processing
        sanitizeHtml: true,
        extractMetadata: true,
    });

    return service;
}

// Run examples
if (require.main === module) {
    main().catch(console.error);
    // handleErrors().catch(console.error);
}

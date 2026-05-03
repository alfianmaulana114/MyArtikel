import { UrlValidator } from "../src/services/UrlValidator";

describe("UrlValidator", () => {
    let validator: UrlValidator;

    beforeEach(() => {
        validator = new UrlValidator();
    });

    describe("URL Validation", () => {
        it("should validate valid HTTP URLs", async () => {
            const result =
                await validator.validateAndNormalize("http://example.com");

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("http://example.com/");
        });

        it("should validate valid HTTPS URLs", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("https://example.com/");
        });

        it("should reject invalid URL formats", async () => {
            const result = await validator.validateAndNormalize("not-a-url");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("Invalid URL format");
        });

        it("should reject unsupported protocols", async () => {
            const result =
                await validator.validateAndNormalize("ftp://example.com");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("Unsupported protocol");
        });

        it("should reject file:// protocol", async () => {
            const result =
                await validator.validateAndNormalize("file:///etc/passwd");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("Unsupported protocol");
        });
    });

    describe("SSRF Protection", () => {
        it("should reject localhost", async () => {
            const result =
                await validator.validateAndNormalize("http://localhost");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("private IP");
        });

        it("should reject 127.0.0.1", async () => {
            const result =
                await validator.validateAndNormalize("http://127.0.0.1");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("private IP");
        });

        it("should reject private IP ranges", async () => {
            const privateIps = [
                "http://192.168.1.1",
                "http://10.0.0.1",
                "http://172.16.0.1",
                "http://172.31.255.255",
            ];

            for (const ip of privateIps) {
                const result = await validator.validateAndNormalize(ip);
                expect(result.isValid).toBe(false);
            }
        });

        it("should reject IPv6 localhost", async () => {
            const result = await validator.validateAndNormalize("http://[::1]");

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("private IP");
        });
    });

    describe("URL Normalization", () => {
        it("should remove tracking parameters", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com?utm_source=test&utm_medium=email",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("https://example.com/");
        });

        it("should remove Facebook tracking parameter", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com?fbclid=abc123",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("https://example.com/");
        });

        it("should remove Google tracking parameter", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com?gclid=abc123",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("https://example.com/");
        });

        it("should preserve legitimate parameters", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com?page=2&sort=date",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe(
                "https://example.com/?page=2&sort=date",
            );
        });

        it("should remove URL fragments", async () => {
            const result = await validator.validateAndNormalize(
                "https://example.com/page#section",
            );

            expect(result.isValid).toBe(true);
            expect(result.normalizedUrl).toBe("https://example.com/page");
        });
    });

    describe("Blocked Hosts", () => {
        it("should reject blocked hosts", async () => {
            const customValidator = new UrlValidator(["malicious.com"]);

            const result = await customValidator.validateAndNormalize(
                "http://malicious.com",
            );

            expect(result.isValid).toBe(false);
            expect(result.error).toContain("blocked");
        });
    });

    describe("Batch Validation", () => {
        it("should validate multiple URLs", async () => {
            const urls = [
                "http://example.com",
                "https://google.com",
                "ftp://invalid.com",
                "http://localhost",
            ];

            const results = await validator.validateMultiple(urls);

            expect(results).toHaveLength(4);
            expect(results[0].isValid).toBe(true);
            expect(results[1].isValid).toBe(true);
            expect(results[2].isValid).toBe(false);
            expect(results[3].isValid).toBe(false);
        });
    });
});

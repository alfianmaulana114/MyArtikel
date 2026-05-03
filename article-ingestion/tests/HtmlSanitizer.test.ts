import { HtmlSanitizer } from "../src/services/HtmlSanitizer";
import { IngestionErrorType } from "../src/types";

describe("HtmlSanitizer", () => {
    let sanitizer: HtmlSanitizer;

    beforeEach(() => {
        sanitizer = new HtmlSanitizer();
    });

    describe("HTML Sanitization", () => {
        it("should remove script tags", () => {
            const html = '<p>Safe content</p><script>alert("XSS")</script>';
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).not.toContain("<script>");
            expect(sanitized).not.toContain("alert");
            expect(sanitized).toContain("Safe content");
        });

        it("should remove onclick attributes", () => {
            const html = "<p onclick=\"alert('XSS')\">Click me</p>";
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).not.toContain("onclick");
            expect(sanitized).not.toContain("alert");
            expect(sanitized).toContain("Click me");
        });

        it("should preserve safe HTML elements", () => {
            const html = "<p>Paragraph</p><strong>Bold</strong><em>Italic</em>";
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).toContain("<p>");
            expect(sanitized).toContain("<strong>");
            expect(sanitized).toContain("<em>");
        });

        it("should remove style tags", () => {
            const html = "<style>body { color: red; }</style><p>Content</p>";
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).not.toContain("<style>");
            expect(sanitized).toContain("Content");
        });

        it("should remove dangerous protocols", () => {
            const html = "<a href=\"javascript:alert('XSS')\">Click me</a>";
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).not.toContain("javascript:");
        });

        it("should allow safe links", () => {
            const html = '<a href="https://example.com">Safe link</a>';
            const sanitized = sanitizer.sanitize(html);

            expect(sanitized).toContain("https://example.com");
            expect(sanitized).toContain("Safe link");
        });
    });

    describe("Text Extraction", () => {
        it("should extract text from HTML", () => {
            const html = "<p>Hello <strong>world</strong>!</p>";
            const text = sanitizer.extractText(html);

            expect(text).toBe("Hello world!");
        });

        it("should handle complex HTML", () => {
            const html = `
        <article>
          <h1>Title</h1>
          <p>First paragraph</p>
          <p>Second paragraph</p>
        </article>
      `;
            const text = sanitizer.extractText(html);

            expect(text).toContain("Title");
            expect(text).toContain("First paragraph");
            expect(text).toContain("Second paragraph");
        });
    });

    describe("Dangerous Content Detection", () => {
        it("should detect script tags", () => {
            const html = '<script>alert("XSS")</script>';
            expect(sanitizer.isDangerous(html)).toBe(true);
        });

        it("should detect javascript: protocol", () => {
            const html = "<a href=\"javascript:alert('XSS')\">Click</a>";
            expect(sanitizer.isDangerous(html)).toBe(true);
        });

        it("should detect onclick attributes", () => {
            const html = "<p onclick=\"alert('XSS')\">Text</p>";
            expect(sanitizer.isDangerous(html)).toBe(true);
        });

        it("should not detect safe content as dangerous", () => {
            const html = "<p>Safe content with <strong>formatting</strong></p>";
            expect(sanitizer.isDangerous(html)).toBe(false);
        });
    });

    describe("Error Handling", () => {
        it("should handle invalid input", () => {
            expect(() => sanitizer.sanitize("")).toThrow();
            expect(() => sanitizer.sanitize(null as any)).toThrow();
            expect(() => sanitizer.sanitize(undefined as any)).toThrow();
        });

        it("should handle very large content", () => {
            const largeContent = "a".repeat(20 * 1024 * 1024); // 20MB
            expect(() =>
                sanitizer.sanitize(`<p>${largeContent}</p>`),
            ).toThrow();
        });
    });

    describe("Sanitization Stats", () => {
        it("should provide accurate sanitization statistics", () => {
            const original =
                "<p>Safe</p><script>Dangerous</script><style>CSS</style>";
            const sanitized = sanitizer.sanitize(original);
            const stats = sanitizer.getSanitizationStats(original, sanitized);

            expect(stats.originalLength).toBe(original.length);
            expect(stats.sanitizedLength).toBe(sanitized.length);
            expect(stats.removedElements).toBeGreaterThan(0);
            expect(stats.safetyScore).toBeGreaterThanOrEqual(0);
            expect(stats.safetyScore).toBeLessThanOrEqual(100);
        });
    });
});

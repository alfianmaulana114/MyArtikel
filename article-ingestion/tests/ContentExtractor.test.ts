import { ContentExtractor } from "../src/services/ContentExtractor";
import { IngestionErrorType } from "../src/types";

describe("ContentExtractor", () => {
    let extractor: ContentExtractor;

    beforeEach(() => {
        extractor = new ContentExtractor();
    });

    describe("Content Extraction", () => {
        it("should extract title from meta tags", () => {
            const html = `
        <html>
          <head>
            <meta property="og:title" content="Test Article Title">
            <title>Fallback Title</title>
          </head>
          <body>
            <h1>H1 Title</h1>
            <p>This is the article content.</p>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.title).toBe("Test Article Title");
            expect(result.content).toContain("This is the article content");
        });

        it("should extract author information", () => {
            const html = `
        <html>
          <head>
            <meta name="author" content="John Doe">
          </head>
          <body>
            <article>
              <p>Article content by John Doe.</p>
            </article>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.author).toBe("John Doe");
        });

        it("should extract published date", () => {
            const html = `
        <html>
          <head>
            <meta property="article:published_time" content="2024-01-15T10:00:00Z">
          </head>
          <body>
            <article>
              <p>Article content.</p>
            </article>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.publishedDate).toBeInstanceOf(Date);
            expect(result.publishedDate?.getFullYear()).toBe(2024);
        });

        it("should extract tags/keywords", () => {
            const html = `
        <html>
          <head>
            <meta name="keywords" content="technology, programming, javascript">
          </head>
          <body>
            <article>
              <p>Article content.</p>
            </article>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.tags).toContain("technology");
            expect(result.tags).toContain("programming");
            expect(result.tags).toContain("javascript");
        });

        it("should calculate word count and reading time", () => {
            const html = `
        <html>
          <body>
            <article>
              <p>This is a test article with several words to test the word counting functionality.</p>
              <p>It should count all the words properly and calculate reading time based on the word count.</p>
            </article>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.wordCount).toBeGreaterThan(0);
            expect(result.readingTime).toBeGreaterThan(0);
        });

        it("should generate excerpt from content", () => {
            const html = `
        <html>
          <body>
            <article>
              <p>This is the first paragraph of the article. It should be used as the excerpt.</p>
              <p>This is the second paragraph that should not be included in the excerpt.</p>
            </article>
          </body>
        </html>
      `;

            const result = extractor.extractContent(
                html,
                "https://example.com",
            );

            expect(result.excerpt).toContain("This is the first paragraph");
            expect(result.excerpt.length).toBeLessThan(300); // Default max excerpt length
        });
    });

    describe("Error Handling", () => {
        it("should throw error for content too short", () => {
            const html = `
        <html>
          <body>
            <p>Short</p>
          </body>
        </html>
      `;

            expect(() =>
                extractor.extractContent(html, "https://example.com"),
            ).toThrow();
        });

        it("should handle empty HTML", () => {
            const html = "<html></html>";

            expect(() =>
                extractor.extractContent(html, "https://example.com"),
            ).toThrow();
        });
    });

    describe("Metadata Extraction", () => {
        it("should extract comprehensive metadata", () => {
            const html = `
        <html lang="en">
          <head>
            <meta property="og:title" content="Test Article">
            <meta name="description" content="Test description">
            <meta name="author" content="Jane Doe">
            <meta property="article:published_time" content="2024-01-15T10:00:00Z">
            <meta property="article:modified_time" content="2024-01-16T10:00:00Z">
            <meta name="keywords" content="test, article, metadata">
          </head>
          <body>
            <article>
              <p>Article content here.</p>
            </article>
          </body>
        </html>
      `;

            const metadata = extractor.extractMetadata(
                extractor["loadHTML"](html),
                "https://example.com",
            );

            expect(metadata.title).toBe("Test Article");
            expect(metadata.description).toBe("Test description");
            expect(metadata.author).toBe("Jane Doe");
            expect(metadata.language).toBe("en");
            expect(metadata.tags).toContain("test");
            expect(metadata.publishedDate).toBeInstanceOf(Date);
            expect(metadata.modifiedDate).toBeInstanceOf(Date);
        });
    });
});

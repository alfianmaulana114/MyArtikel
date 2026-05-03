import * as cheerio from "cheerio";
import {
    ArticleContent,
    ArticleMetadata,
    IngestionError,
    IngestionErrorType,
} from "../types";

/**
 * Service untuk content extraction dari HTML menggunakan berbagai algoritma
 */
export class ContentExtractor {
    private readonly minContentLength = 100; // minimum characters
    private readonly maxTitleLength = 200;
    private readonly maxExcerptLength = 300;

    /**
     * Extract article content dari HTML
     */
    extractContent(html: string, url: string): ArticleContent {
        try {
            const $ = cheerio.load(html);

            // Remove unwanted elements
            this.removeUnwantedElements($);

            // Extract metadata
            const metadata = this.extractMetadata($, url);

            // Extract main content menggunakan multiple algorithms
            const mainContent = this.extractMainContent($);

            if (mainContent.length < this.minContentLength) {
                throw this.createError(
                    IngestionErrorType.EXTRACTION_FAILED,
                    `Content too short: ${mainContent.length} characters (minimum ${this.minContentLength})`,
                    false,
                );
            }

            // Generate excerpt
            const excerpt = this.generateExcerpt(mainContent);

            // Calculate word count and reading time
            const wordCount = this.countWords(mainContent);
            const readingTime = Math.ceil(wordCount / 200); // 200 words per minute average

            return {
                title: metadata.title,
                content: mainContent,
                excerpt,
                author: metadata.author,
                publishedDate: metadata.publishedDate,
                wordCount,
                readingTime,
                language: metadata.language,
                tags: metadata.tags,
            };
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error;
            }

            throw this.createError(
                IngestionErrorType.EXTRACTION_FAILED,
                `Content extraction failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                false,
            );
        }
    }

    /**
     * Extract metadata dari HTML
     */
    extractMetadata($: cheerio.CheerioAPI, url: string): ArticleMetadata {
        // Title extraction dengan multiple fallbacks
        const title = this.extractTitle($);

        // Author extraction
        const author = this.extractAuthor($);

        // Published date extraction
        const publishedDate = this.extractPublishedDate($);
        const modifiedDate = this.extractModifiedDate($);

        // Description/Excerpt
        const description = this.extractDescription($);

        // Language detection
        const language = this.extractLanguage($);

        // Tags extraction
        const tags = this.extractTags($);

        // Word count dan reading time (akan dihitung setelah content extraction)
        const wordCount = 0;
        const readingTime = 0;

        // Images extraction
        const images = this.extractImages($);

        return {
            url,
            title,
            description,
            author,
            publishedDate,
            modifiedDate,
            language,
            tags,
            wordCount,
            readingTime,
            images,
        };
    }

    /**
     * Remove unwanted elements dari HTML
     */
    private removeUnwantedElements($: cheerio.CheerioAPI): void {
        const unwantedSelectors = [
            "script",
            "style",
            "nav",
            "header",
            "footer",
            "aside",
            ".advertisement",
            ".ads",
            ".social-share",
            ".comments",
            ".related-posts",
            ".sidebar",
            ".menu",
            ".navigation",
            '[role="navigation"]',
            '[role="banner"]',
            '[role="contentinfo"]',
            'iframe[src*="facebook.com"]',
            'iframe[src*="twitter.com"]',
            ".cookie-notice",
            ".newsletter-signup",
            ".popup",
            ".modal",
        ];

        unwantedSelectors.forEach((selector) => {
            try {
                $(selector).remove();
            } catch (error) {
                // Ignore errors untuk individual selectors
            }
        });
    }

    /**
     * Extract title dengan multiple fallbacks
     */
    private extractTitle($: cheerio.CheerioAPI): string {
        const titleSelectors = [
            'meta[property="og:title"]',
            'meta[name="twitter:title"]',
            'meta[name="title"]',
            "title",
            "h1",
            ".post-title",
            ".entry-title",
            ".article-title",
            '[itemprop="headline"]',
        ];

        for (const selector of titleSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const title = element.attr("content") || element.text();
                if (title && title.trim().length > 0) {
                    return this.cleanText(title.trim()).substring(
                        0,
                        this.maxTitleLength,
                    );
                }
            }
        }

        return "Untitled Article";
    }

    /**
     * Extract author dengan multiple fallbacks
     */
    private extractAuthor($: cheerio.CheerioAPI): string | undefined {
        const authorSelectors = [
            'meta[name="author"]',
            'meta[property="article:author"]',
            'meta[name="twitter:creator"]',
            '[itemprop="author"]',
            ".author",
            ".byline",
            ".post-author",
            ".entry-author",
        ];

        for (const selector of authorSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const author = element.attr("content") || element.text();
                if (author && author.trim().length > 0) {
                    return this.cleanText(author.trim());
                }
            }
        }

        return undefined;
    }

    /**
     * Extract published date
     */
    private extractPublishedDate($: cheerio.CheerioAPI): Date | undefined {
        const dateSelectors = [
            'meta[property="article:published_time"]',
            'meta[name="date"]',
            'meta[name="DC.date.issued"]',
            'meta[name="dcterms.created"]',
            '[itemprop="datePublished"]',
            ".published",
            ".post-date",
            ".entry-date",
            "time[datetime]",
        ];

        for (const selector of dateSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const dateStr =
                    element.attr("content") ||
                    element.attr("datetime") ||
                    element.text();

                if (dateStr) {
                    const date = new Date(dateStr);
                    if (!isNaN(date.getTime())) {
                        return date;
                    }
                }
            }
        }

        return undefined;
    }

    /**
     * Extract modified date
     */
    private extractModifiedDate($: cheerio.CheerioAPI): Date | undefined {
        const dateSelectors = [
            'meta[property="article:modified_time"]',
            'meta[name="dcterms.modified"]',
            '[itemprop="dateModified"]',
            ".updated",
            ".modified",
        ];

        for (const selector of dateSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const dateStr = element.attr("content") || element.text();
                if (dateStr) {
                    const date = new Date(dateStr);
                    if (!isNaN(date.getTime())) {
                        return date;
                    }
                }
            }
        }

        return undefined;
    }

    /**
     * Extract description
     */
    private extractDescription($: cheerio.CheerioAPI): string | undefined {
        const descSelectors = [
            'meta[name="description"]',
            'meta[property="og:description"]',
            'meta[name="twitter:description"]',
            'meta[name="DC.description"]',
            '[itemprop="description"]',
            ".summary",
            ".excerpt",
        ];

        for (const selector of descSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const desc = element.attr("content") || element.text();
                if (desc && desc.trim().length > 0) {
                    return this.cleanText(desc.trim());
                }
            }
        }

        return undefined;
    }

    /**
     * Extract language
     */
    private extractLanguage($: cheerio.CheerioAPI): string | undefined {
        const langSelectors = [
            "html[lang]",
            'meta[name="language"]',
            'meta[property="og:locale"]',
            '[itemprop="inLanguage"]',
        ];

        for (const selector of langSelectors) {
            const element = $(selector).first();
            if (element.length > 0) {
                const lang =
                    element.attr("lang") ||
                    element.attr("content") ||
                    element.attr("content")?.split("-")[0];

                if (lang && lang.trim().length > 0) {
                    return lang.trim().toLowerCase();
                }
            }
        }

        return undefined;
    }

    /**
     * Extract tags/keywords
     */
    private extractTags($: cheerio.CheerioAPI): string[] {
        const tagSelectors = [
            'meta[name="keywords"]',
            'meta[name="news_keywords"]',
            'meta[property="article:tag"]',
            '[itemprop="keywords"]',
            ".tags a",
            ".tag",
            ".post-tag",
        ];

        const tags: string[] = [];
        const seen = new Set<string>();

        for (const selector of tagSelectors) {
            $(selector).each((_, element) => {
                const tagText = $(element).attr("content") || $(element).text();
                if (tagText) {
                    const tagList = tagText
                        .split(",")
                        .map((tag) => this.cleanText(tag.trim()));
                    tagList.forEach((tag) => {
                        if (tag.length > 0 && !seen.has(tag.toLowerCase())) {
                            tags.push(tag);
                            seen.add(tag.toLowerCase());
                        }
                    });
                }
            });
        }

        return tags;
    }

    /**
     * Extract images
     */
    private extractImages($: cheerio.CheerioAPI): string[] {
        const imageSelectors = [
            'meta[property="og:image"]',
            'meta[name="twitter:image"]',
            'meta[name="image"]',
            "img[src]",
        ];

        const images: string[] = [];
        const seen = new Set<string>();

        for (const selector of imageSelectors) {
            $(selector).each((_, element) => {
                const src =
                    $(element).attr("content") || $(element).attr("src");
                if (src && src.length > 0 && !seen.has(src)) {
                    images.push(src);
                    seen.add(src);
                }
            });
        }

        return images;
    }

    /**
     * Extract main content menggunakan readability-style algorithm
     */
    private extractMainContent($: cheerio.CheerioAPI): string {
        // Candidate selectors untuk main content
        const contentSelectors = [
            "article",
            '[role="main"]',
            "main",
            ".post-content",
            ".entry-content",
            ".article-content",
            ".content",
            ".post-body",
            ".entry-body",
            '[itemprop="articleBody"]',
            ".article-body",
        ];

        let bestContent = "";
        let bestScore = 0;

        // Coba setiap selector
        for (const selector of contentSelectors) {
            const elements = $(selector);
            elements.each((_, element) => {
                const content = this.extractTextFromElement($, $(element));
                const score = this.scoreContent(content);

                if (score > bestScore) {
                    bestScore = score;
                    bestContent = content;
                }
            });
        }

        // Fallback: extract dari body jika tidak ada yang cukup bagus
        if (bestScore < 50) {
            const bodyContent = this.extractTextFromElement($, $("body"));
            if (bodyContent.length > bestContent.length) {
                bestContent = bodyContent;
            }
        }

        return this.cleanText(bestContent);
    }

    /**
     * Extract text dari element dengan proper formatting
     */
    private extractTextFromElement(
        $: cheerio.CheerioAPI,
        element: cheerio.Cheerio,
    ): string {
        // Clone element untuk avoid modifying original
        const $clone = element.clone();

        // Remove remaining unwanted elements
        $clone.find("script, style, nav, header, footer, aside").remove();

        // Extract text dengan proper spacing
        let text = "";
        $clone
            .find("p, div, article, section, h1, h2, h3, h4, h5, h6, li")
            .each((_, el) => {
                const $el = $(el);
                const elementText = $el.text().trim();
                if (elementText.length > 0) {
                    text += elementText + "\n\n";
                }
            });

        return text;
    }

    /**
     * Score content berdasarkan berbagai factors
     */
    private scoreContent(content: string): number {
        if (content.length < this.minContentLength) {
            return 0;
        }

        let score = 0;

        // Length score
        score += Math.min(content.length / 100, 50);

        // Sentence density
        const sentences = content
            .split(/[.!?]+/)
            .filter((s) => s.trim().length > 0);
        const words = content.split(/\s+/).filter((w) => w.length > 0);
        if (words.length > 0) {
            score += Math.min((sentences.length / words.length) * 100, 30);
        }

        // Paragraph structure
        const paragraphs = content
            .split(/\n\n+/)
            .filter((p) => p.trim().length > 0);
        if (paragraphs.length > 2) {
            score += 20;
        }

        return score;
    }

    /**
     * Generate excerpt dari content
     */
    private generateExcerpt(content: string): string {
        const sentences = content
            .split(/[.!?]+/)
            .filter((s) => s.trim().length > 0);

        if (sentences.length === 0) {
            return content.substring(0, this.maxExcerptLength) + "...";
        }

        let excerpt = "";
        for (const sentence of sentences) {
            if ((excerpt + sentence).length > this.maxExcerptLength) {
                break;
            }
            excerpt += sentence + ". ";
        }

        return (
            excerpt.trim() ||
            content.substring(0, this.maxExcerptLength) + "..."
        );
    }

    /**
     * Count words dalam text
     */
    private countWords(text: string): number {
        return text.split(/\s+/).filter((word) => word.length > 0).length;
    }

    /**
     * Clean text dari extra whitespace dan special characters
     */
    private cleanText(text: string): string {
        return text
            .replace(/\s+/g, " ")
            .replace(/\n\s*\n/g, "\n\n")
            .replace(/^\s+|\s+$/g, "")
            .trim();
    }

    /**
     * Create formatted error
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
}

import createDOMPurify from "dompurify";
import { JSDOM } from "jsdom";
import { IngestionError, IngestionErrorType } from "../types";

/**
 * Service untuk HTML sanitization dengan security controls
 */
export class HtmlSanitizer {
    private readonly dompurify: any;

    constructor() {
        const window = new JSDOM("").window;
        this.dompurify = createDOMPurify(window as any);

        // Configure DOMPurify untuk maximum security
        this.dompurify.setConfig({
            ALLOWED_TAGS: [
                "p",
                "div",
                "span",
                "h1",
                "h2",
                "h3",
                "h4",
                "h5",
                "h6",
                "strong",
                "em",
                "b",
                "i",
                "u",
                "strike",
                "del",
                "ins",
                "blockquote",
                "code",
                "pre",
                "br",
                "hr",
                "ul",
                "ol",
                "li",
                "dl",
                "dt",
                "dd",
                "table",
                "thead",
                "tbody",
                "tfoot",
                "tr",
                "td",
                "th",
                "a",
                "img",
                "figure",
                "figcaption",
                "article",
                "section",
                "header",
                "footer",
                "aside",
                "nav",
                "main",
                "time",
                "mark",
            ],
            ALLOWED_ATTR: [
                "href",
                "src",
                "alt",
                "title",
                "width",
                "height",
                "class",
                "id",
                "datetime",
                "cite",
            ],
            ALLOW_DATA_ATTR: false,
            ALLOW_UNKNOWN_PROTOCOLS: false,
            SAFE_FOR_TEMPLATES: true,
            WHOLE_DOCUMENT: true,
            RETURN_DOM: false,
            RETURN_DOM_FRAGMENT: false,
            RETURN_DOM_IMPORT: false,
            SANITIZE_DOM: true,
            KEEP_CONTENT: true,
            FORBID_TAGS: [
                "style",
                "script",
                "object",
                "embed",
                "form",
                "input",
                "textarea",
            ],
            FORBID_ATTR: [
                "style",
                "onclick",
                "onload",
                "onerror",
                "onmouseover",
            ],
            ALLOW_ARIA_ATTR: false,
            ALLOW_CUSTOM_ELEMENTS: false,
        });
    }

    /**
     * Sanitize HTML content untuk security
     */
    sanitize(html: string): string {
        try {
            if (!html || typeof html !== "string") {
                throw this.createError(
                    IngestionErrorType.SANITIZATION_FAILED,
                    "Invalid HTML input",
                    false,
                );
            }

            // Sanitize menggunakan DOMPurify
            const sanitized = this.dompurify.sanitize(html, {
                RETURN_TRUSTED_TYPE: false,
            });

            if (!sanitized || sanitized.trim().length === 0) {
                throw this.createError(
                    IngestionErrorType.SANITIZATION_FAILED,
                    "Sanitization resulted in empty content",
                    false,
                );
            }

            // Additional validation
            this.validateSanitizedContent(sanitized);

            return sanitized;
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error;
            }

            throw this.createError(
                IngestionErrorType.SANITIZATION_FAILED,
                `HTML sanitization failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                false,
            );
        }
    }

    /**
     * Sanitize dengan custom configuration
     */
    sanitizeWithConfig(html: string, config: any): string {
        try {
            if (!html || typeof html !== "string") {
                throw this.createError(
                    IngestionErrorType.SANITIZATION_FAILED,
                    "Invalid HTML input",
                    false,
                );
            }

            const sanitized = this.dompurify.sanitize(html, config);

            this.validateSanitizedContent(sanitized);

            return sanitized;
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error;
            }

            throw this.createError(
                IngestionErrorType.SANITIZATION_FAILED,
                `Custom sanitization failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                false,
            );
        }
    }

    /**
     * Extract text dari HTML (remove all tags)
     */
    extractText(html: string): string {
        try {
            const sanitized = this.sanitize(html);

            // Create temporary DOM untuk extract text
            const tempWindow = new JSDOM(sanitized).window;
            const text = tempWindow.document.body.textContent || "";

            return text.trim();
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error;
            }

            throw this.createError(
                IngestionErrorType.SANITIZATION_FAILED,
                `Text extraction failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                false,
            );
        }
    }

    /**
     * Validate sanitized content
     */
    private validateSanitizedContent(sanitized: string): void {
        // Check untuk potential XSS vectors yang mungkin lolos
        const dangerousPatterns = [
            /<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/gi,
            /javascript:/gi,
            /on\w+\s*=/gi,
            /data:text\/html/gi,
            /vbscript:/gi,
            /file:/gi,
        ];

        for (const pattern of dangerousPatterns) {
            if (pattern.test(sanitized)) {
                throw this.createError(
                    IngestionErrorType.SANITIZATION_FAILED,
                    `Dangerous content detected: ${pattern.toString()}`,
                    false,
                );
            }
        }

        // Check content length
        if (sanitized.length > 10 * 1024 * 1024) {
            // 10MB limit
            throw this.createError(
                IngestionErrorType.SANITIZATION_FAILED,
                "Sanitized content exceeds maximum size limit",
                false,
            );
        }
    }

    /**
     * Check if HTML contains dangerous content
     */
    isDangerous(html: string): boolean {
        try {
            const dangerousPatterns = [
                /<script[\s\S]*?>[\s\S]*?<\/script>/gi,
                /javascript:/gi,
                /on\w+\s*=/gi,
                /data:text\/html/gi,
                /vbscript:/gi,
                /<iframe[\s\S]*?src=["']javascript:/gi,
                /<object[\s\S]*?>/gi,
                /<embed[\s\S]*?>/gi,
                /<form[\s\S]*?>/gi,
                /<input[\s\S]*?>/gi,
                /<textarea[\s\S]*?>/gi,
            ];

            return dangerousPatterns.some((pattern) => pattern.test(html));
        } catch {
            return true; // Assume dangerous jika parsing gagal
        }
    }

    /**
     * Get sanitization stats
     */
    getSanitizationStats(
        original: string,
        sanitized: string,
    ): {
        originalLength: number;
        sanitizedLength: number;
        removedElements: number;
        safetyScore: number;
    } {
        const originalLength = original.length;
        const sanitizedLength = sanitized.length;
        const removedElements =
            (original.match(/<[^>]+>/g) || []).length -
            (sanitized.match(/<[^>]+>/g) || []).length;

        // Calculate safety score (0-100)
        const safetyScore = Math.min(
            100,
            Math.max(0, (sanitizedLength / originalLength) * 100),
        );

        return {
            originalLength,
            sanitizedLength,
            removedElements,
            safetyScore: Math.round(safetyScore),
        };
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

import axios, { AxiosInstance, AxiosRequestConfig, AxiosResponse } from "axios";
import { lookup } from "dns";
import { promisify } from "util";
import { SafeHttpResponse, IngestionError, IngestionErrorType } from "../types";
import { UrlValidator } from "./UrlValidator";

const dnsLookup = promisify(lookup);

/**
 * HTTP client dengan SSRF protection dan security controls
 */
export class SafeHttpClient {
    private readonly axiosInstance: AxiosInstance;
    private readonly urlValidator: UrlValidator;
    private readonly maxContentSize: number;
    private readonly maxRedirects: number;
    private readonly requestTimeout: number;

    constructor(
        urlValidator: UrlValidator,
        config: {
            maxContentSize: number;
            maxRedirects: number;
            requestTimeout: number;
            userAgent: string;
        },
    ) {
        this.urlValidator = urlValidator;
        this.maxContentSize = maxContentSize;
        this.maxRedirects = maxRedirects;
        this.requestTimeout = requestTimeout;

        this.axiosInstance = axios.create({
            timeout: requestTimeout,
            maxRedirects: maxRedirects,
            headers: {
                "User-Agent": config.userAgent,
                Accept: "text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8",
                "Accept-Language": "en-US,en;q=0.5",
                "Accept-Encoding": "gzip, deflate",
                Connection: "keep-alive",
                "Upgrade-Insecure-Requests": "1",
            },
            validateStatus: (status) => status >= 200 && status < 300,
            responseType: "text",
            decompress: true,
            maxContentLength: maxContentSize,
            transitional: {
                clarifyTimeoutError: true,
            },
        });

        this.setupInterceptors();
    }

    /**
     * Setup axios interceptors untuk security dan validation
     */
    private setupInterceptors(): void {
        // Request interceptor untuk URL validation
        this.axiosInstance.interceptors.request.use(
            async (config) => {
                const url = config.url;
                if (!url) {
                    throw new Error("URL is required");
                }

                // Validasi URL sebelum request
                const validation =
                    await this.urlValidator.validateAndNormalize(url);
                if (!validation.isValid) {
                    throw new Error(
                        `URL validation failed: ${validation.error}`,
                    );
                }

                // Update URL dengan normalized version
                config.url = validation.normalizedUrl;

                return config;
            },
            (error) => Promise.reject(error),
        );

        // Response interceptor untuk content validation
        this.axiosInstance.interceptors.response.use(
            (response) => {
                // Validasi content size
                const contentLength = response.headers["content-length"];
                if (
                    contentLength &&
                    parseInt(contentLength) > this.maxContentSize
                ) {
                    throw new Error(
                        `Content too large: ${contentLength} bytes`,
                    );
                }

                // Validasi content type
                const contentType = response.headers["content-type"];
                if (!contentType || !contentType.includes("text/html")) {
                    throw new Error(`Unsupported content type: ${contentType}`);
                }

                return response;
            },
            (error) => {
                if (error.response) {
                    // Server responded with error status
                    throw new Error(
                        `HTTP ${error.response.status}: ${error.response.statusText}`,
                    );
                } else if (error.request) {
                    // Request made but no response received
                    throw new Error("Network error: No response received");
                } else {
                    // Something else happened
                    throw new Error(`Request error: ${error.message}`);
                }
            },
        );
    }

    /**
     * Fetch content dengan SSRF protection
     */
    async fetch(url: string): Promise<SafeHttpResponse> {
        try {
            // Validasi URL sebelum fetch
            const validation =
                await this.urlValidator.validateAndNormalize(url);
            if (!validation.isValid) {
                throw this.createError(
                    IngestionErrorType.INVALID_URL,
                    `URL validation failed: ${validation.error}`,
                    false,
                );
            }

            // DNS validation untuk SSRF protection
            await this.validateDns(validation.normalizedUrl);

            // Execute HTTP request
            const response = await this.axiosInstance.get(
                validation.normalizedUrl,
            );

            // Validasi final content
            const content = response.data;
            if (content.length > this.maxContentSize) {
                throw this.createError(
                    IngestionErrorType.CONTENT_TOO_LARGE,
                    `Content size ${content.length} exceeds maximum ${this.maxContentSize}`,
                    false,
                );
            }

            return {
                url: response.config.url || validation.normalizedUrl,
                statusCode: response.status,
                headers: response.headers,
                content: content,
                contentType: response.headers["content-type"] || "text/html",
                contentLength: content.length,
                redirected: response.request._redirectCount > 0,
                redirectUrls:
                    response.request._redirectable?._redirectHistory || [],
            };
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error; // Already formatted error
            }

            throw this.createError(
                IngestionErrorType.NETWORK_ERROR,
                `HTTP request failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                true,
            );
        }
    }

    /**
     * DNS validation untuk SSRF protection
     */
    private async validateDns(url: string): Promise<void> {
        try {
            const parsedUrl = new URL(url);
            const hostname = parsedUrl.hostname;

            // Skip DNS validation untuk IP addresses (sudah divalidasi di UrlValidator)
            if (/^\d+\.\d+\.\d+\.\d+$/.test(hostname)) {
                return;
            }

            // DNS lookup untuk hostname
            const { address } = await dnsLookup(hostname);

            // Re-validate resolved IP
            const ipValidation = await this.urlValidator.validateAndNormalize(
                `http://${address}`,
            );
            if (!ipValidation.isValid) {
                throw this.createError(
                    IngestionErrorType.SSRF_BLOCKED,
                    `DNS resolution blocked: ${ipValidation.error}`,
                    false,
                );
            }
        } catch (error) {
            if (error instanceof Error && "type" in error) {
                throw error;
            }

            throw this.createError(
                IngestionErrorType.NETWORK_ERROR,
                `DNS validation failed: ${error instanceof Error ? error.message : "Unknown error"}`,
                true,
            );
        }
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

    /**
     * Check if URL is reachable (head request)
     */
    async isReachable(url: string): Promise<boolean> {
        try {
            await this.fetch(url);
            return true;
        } catch {
            return false;
        }
    }
}

import {
    UrlValidationResult,
    IngestionError,
    IngestionErrorType,
} from "./types";
import { URL } from "url";
import { dns } from "dns";
import { promisify } from "util";

const lookup = promisify(dns.lookup);

/**
 * Service untuk URL validation dan normalization dengan SSRF protection
 */
export class UrlValidator {
    private readonly blockedHosts: Set<string>;
    private readonly privateIpRanges: RegExp[];
    private readonly allowedSchemes: Set<string>;

    constructor(
        blockedHosts: string[] = [],
        privateIpRanges: string[] = [
            "^127\\.",
            "^10\\.",
            "^172\\.(1[6-9]|2[0-9]|3[01])\\.",
            "^192\\.168\\.",
            "^169\\.254\\.",
            "^::1$",
            "^fc00:",
            "^fe80:",
        ],
        allowedSchemes: string[] = ["http:", "https:"],
    ) {
        this.blockedHosts = new Set(blockedHosts.map((h) => h.toLowerCase()));
        this.privateIpRanges = privateIpRanges.map(
            (range) => new RegExp(range),
        );
        this.allowedSchemes = new Set(allowedSchemes);
    }

    /**
     * Validasi dan normalisasi URL dengan SSRF protection
     */
    async validateAndNormalize(url: string): Promise<UrlValidationResult> {
        try {
            // Parse URL
            let parsedUrl: URL;
            try {
                parsedUrl = new URL(url);
            } catch (error) {
                return {
                    isValid: false,
                    normalizedUrl: url,
                    error: "Invalid URL format",
                };
            }

            // Validasi scheme
            if (!this.allowedSchemes.has(parsedUrl.protocol)) {
                return {
                    isValid: false,
                    normalizedUrl: url,
                    error: `Unsupported protocol: ${parsedUrl.protocol}. Only HTTP/HTTPS are allowed.`,
                };
            }

            // Remove tracking parameters dan fragment
            const cleanUrl = this.removeTrackingParams(parsedUrl);

            // Re-parse setelah cleaning
            parsedUrl = new URL(cleanUrl);

            // Validasi hostname
            const hostname = parsedUrl.hostname.toLowerCase();

            // Cek blocked hosts
            if (this.blockedHosts.has(hostname)) {
                return {
                    isValid: false,
                    normalizedUrl: cleanUrl,
                    error: "Hostname is blocked",
                };
            }

            // Cek localhost dan IP addresses
            if (this.isPrivateIpOrLocalhost(hostname)) {
                return {
                    isValid: false,
                    normalizedUrl: cleanUrl,
                    error: "Private IP addresses and localhost are not allowed",
                };
            }

            // DNS resolution check untuk SSRF protection
            try {
                const { address } = await lookup(hostname);
                if (this.isPrivateIpOrLocalhost(address)) {
                    return {
                        isValid: false,
                        normalizedUrl: cleanUrl,
                        error: "Resolved IP is a private IP address",
                    };
                }
            } catch (error) {
                return {
                    isValid: false,
                    normalizedUrl: cleanUrl,
                    error: "DNS resolution failed",
                };
            }

            // Canonical URL detection (akan diimplementasikan di service lain)
            return {
                isValid: true,
                normalizedUrl: cleanUrl,
                canonicalUrl: cleanUrl, // Placeholder untuk canonical URL
            };
        } catch (error) {
            return {
                isValid: false,
                normalizedUrl: url,
                error: "URL validation failed",
            };
        }
    }

    /**
     * Remove tracking parameters dari URL
     */
    private removeTrackingParams(url: URL): string {
        const trackingParams = [
            "utm_source",
            "utm_medium",
            "utm_campaign",
            "utm_term",
            "utm_content",
            "fbclid",
            "gclid",
            "dclid",
            "msclkid",
            "twclid",
            "li_fat_id",
            "mc_cid",
            "mc_eid",
            "_ga",
            "_gid",
            "_gac",
            "gb_source",
            "gb_medium",
            "gb_campaign",
            "gb_keyword",
            "gb_content",
            "yclid",
            "pk_campaign",
            "pk_kwd",
            "pk_keyword",
            "pk_source",
            "pk_medium",
            "pk_content",
            "pk_cid",
            "piwik_campaign",
            "piwik_kwd",
            "piwik_keyword",
            "mtm_source",
            "mtm_medium",
            "mtm_campaign",
            "mtm_keyword",
            "mtm_content",
            "mtm_cid",
            "mtm_group",
            "mtm_placement",
            "matomo_source",
            "matomo_medium",
            "matomo_campaign",
            "matomo_keyword",
            "matomo_content",
            "matomo_cid",
            "matomo_group",
            "matomo_placement",
        ];

        const params = new URLSearchParams(url.search);

        // Remove tracking parameters
        trackingParams.forEach((param) => {
            params.delete(param);
        });

        // Build clean URL
        const cleanUrl = new URL(url);
        cleanUrl.search = params.toString();
        cleanUrl.hash = ""; // Remove fragment

        return cleanUrl.toString();
    }

    /**
     * Cek apakah hostname adalah private IP atau localhost
     */
    private isPrivateIpOrLocalhost(hostname: string): boolean {
        // Cek IP address format
        const ipRegex = /^\d+\.\d+\.\d+\.\d+$/;
        const ipv6Regex = /^[0-9a-fA-F:]+$/;

        if (ipRegex.test(hostname) || ipv6Regex.test(hostname)) {
            return this.privateIpRanges.some((range) => range.test(hostname));
        }

        // Cek localhost variants
        const localhostVariants = [
            "localhost",
            "localhost.",
            "local",
            "127.0.0.1",
            "::1",
        ];
        return localhostVariants.includes(hostname.toLowerCase());
    }

    /**
     * Validasi multiple URLs sekaligus
     */
    async validateMultiple(urls: string[]): Promise<UrlValidationResult[]> {
        return Promise.all(urls.map((url) => this.validateAndNormalize(url)));
    }
}

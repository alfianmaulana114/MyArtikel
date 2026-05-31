<?php

namespace App\Services;

use Exception;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleExtractionService
{
    private int $timeout = 30;

    private int $maxContentLength = 50000; // 50KB max

    /**
     * Extract article content from URL
     */
    public function extractFromUrl(string $url): array
    {
        try {
            // Validate URL
            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                throw new Exception('Invalid URL provided');
            }

            // Check cache first
            $cacheKey = 'article_extract:v2:'.md5($url);
            if ($cached = Cache::get($cacheKey)) {
                return $cached;
            }

            $response = $this->fetchHtml($url);

            if (! $response->successful()) {
                $status = $response->status();
                if ($status === 403) {
                    throw new Exception('Failed to fetch URL: 403 (Access denied by source site)');
                }
                if ($status === 429) {
                    throw new Exception('Failed to fetch URL: 429 (Rate limited by source site)');
                }

                throw new Exception('Failed to fetch URL: '.$status);
            }

            $html = $response->body();
            $extractedData = $this->extractContent($html, $url);

            // Cache for 1 hour
            Cache::put($cacheKey, $extractedData, now()->addHour());

            return $extractedData;

        } catch (Exception $e) {
            Log::error('Article extraction failed', [
                'url' => $url,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'title' => '',
                'content' => '',
                'excerpt' => '',
                'image' => null,
                'tags' => [],
                'metadata' => [],
            ];
        }
    }

    private function fetchHtml(string $url): Response
    {
        $client = Http::timeout($this->timeout)
            ->withOptions([
                'allow_redirects' => true,
                'max_redirects' => 5,
            ])
            ->withHeaders([
                'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36',
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'id-ID,id;q=0.9,en-US;q=0.8,en;q=0.7',
                'Cache-Control' => 'no-cache',
                'Pragma' => 'no-cache',
            ]);

        $response = $client->get($url);
        if ($response->status() !== 403) {
            return $response;
        }

        return $client->withHeaders([
            'Referer' => 'https://www.google.com/',
            'Sec-Fetch-Site' => 'cross-site',
            'Sec-Fetch-Mode' => 'navigate',
            'Sec-Fetch-Dest' => 'document',
            'Upgrade-Insecure-Requests' => '1',
        ])->get($url);
    }

    /**
     * Extract content from HTML
     */
    private function extractContent(string $html, string $url): array
    {
        try {
            // Basic HTML cleanup
            $html = $this->cleanHtml($html);

            // Extract title
            $title = $this->extractTitle($html);

            // Extract main content
            $content = $this->extractMainContent($html);

            // Extract excerpt
            $excerpt = $this->extractExcerpt($content);

            // Extract featured image
            $image = $this->extractImage($html, $url);

            // Extract tags/keywords
            $tags = $this->extractTags($html);

            // Extract metadata
            $metadata = $this->extractMetadata($html, $url);

            return [
                'success' => true,
                'title' => $title,
                'content' => $content,
                'excerpt' => $excerpt,
                'image' => $image,
                'tags' => $tags,
                'metadata' => $metadata,
            ];

        } catch (Exception $e) {
            throw new Exception('Content extraction failed: '.$e->getMessage());
        }
    }

    /**
     * Clean HTML content
     */
    private function cleanHtml(string $html): string
    {
        // Remove scripts and styles
        $html = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $html);
        $html = preg_replace('/<style\b[^<]*(?:(?!<\/style>)<[^<]*)*<\/style>/mi', '', $html);

        // Remove comments
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        return $html;
    }

    /**
     * Extract title from HTML
     */
    private function extractTitle(string $html): string
    {
        // Try meta title first
        if (preg_match('/<meta[^>]*property="og:title"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        if (preg_match('/<meta[^>]*name="twitter:title"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        // Fallback to title tag
        if (preg_match('/<title[^>]*>([^<]+)<\/title>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        // Fallback to h1
        if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/i', $html, $matches)) {
            return trim($matches[1]);
        }

        return 'Untitled Article';
    }

    /**
     * Extract main content from HTML
     */
    private function extractMainContent(string $html): string
    {
        libxml_use_internal_errors(true);
        $dom = new \DOMDocument;
        $dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();

        $xpath = new \DOMXPath($dom);

        $candidates = [
            "//*[local-name()='article']",
            "//*[@role='main']",
            "//*[local-name()='main']",
            "//*[@id='mw-content-text']",
            "//*[@id='bodyContent']",
            "//*[@id='content']",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' article-content ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' entry-content ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' post-content ')]",
            "//*[contains(concat(' ', normalize-space(@class), ' '), ' main-content ')]",
        ];

        foreach ($candidates as $expr) {
            $node = $xpath->query($expr)->item(0);
            if ($node) {
                return $this->cleanText($node->textContent ?? '');
            }
        }

        $body = $xpath->query('//body')->item(0);
        if ($body) {
            return $this->cleanText($body->textContent ?? '');
        }

        return $this->cleanText($dom->textContent ?? '');
    }

    /**
     * Extract excerpt from content
     */
    private function extractExcerpt(string $content, int $maxLength = 300): string
    {
        $content = strip_tags($content);
        $content = preg_replace('/\s+/', ' ', $content);

        if (strlen($content) <= $maxLength) {
            return trim($content);
        }

        $excerpt = substr($content, 0, $maxLength);
        $lastSpace = strrpos($excerpt, ' ');

        if ($lastSpace !== false) {
            $excerpt = substr($excerpt, 0, $lastSpace);
        }

        return trim($excerpt).'...';
    }

    /**
     * Extract featured image
     */
    private function extractImage(string $html, string $url): ?string
    {
        // Try OpenGraph image
        if (preg_match('/<meta[^>]*property="og:image"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            return $this->resolveUrl($matches[1], $url);
        }

        // Try Twitter image
        if (preg_match('/<meta[^>]*name="twitter:image"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            return $this->resolveUrl($matches[1], $url);
        }

        // Try first significant image
        if (preg_match('/<img[^>]*src="([^"]*)"[^>]*>/i', $html, $matches)) {
            return $this->resolveUrl($matches[1], $url);
        }

        return null;
    }

    /**
     * Extract tags/keywords
     */
    private function extractTags(string $html): array
    {
        $tags = [];

        // Try meta keywords
        if (preg_match('/<meta[^>]*name="keywords"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            $keywords = explode(',', $matches[1]);
            foreach ($keywords as $keyword) {
                $tag = trim($keyword);
                if (! empty($tag)) {
                    $tags[] = $tag;
                }
            }
        }

        // Try to extract from common tag elements
        if (preg_match_all('/<(?:a|span)[^>]*class="[^"]*tag[^"]*"[^>]*>([^<]+)<\/(?:a|span)>/i', $html, $matches)) {
            foreach ($matches[1] as $tag) {
                $tag = trim(strip_tags($tag));
                if (! empty($tag)) {
                    $tags[] = $tag;
                }
            }
        }

        return array_unique(array_slice($tags, 0, 10)); // Max 10 tags
    }

    /**
     * Extract metadata
     */
    private function extractMetadata(string $html, string $url): array
    {
        $metadata = [
            'url' => $url,
            'extracted_at' => now()->toIso8601String(),
        ];

        // Extract author
        if (preg_match('/<meta[^>]*name="author"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            $metadata['author'] = trim($matches[1]);
        }

        // Extract description
        if (preg_match('/<meta[^>]*name="description"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            $metadata['description'] = trim($matches[1]);
        }

        // Extract publish date
        if (preg_match('/<meta[^>]*property="article:published_time"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            $metadata['published_time'] = trim($matches[1]);
        }

        // Extract site name
        if (preg_match('/<meta[^>]*property="og:site_name"[^>]*content="([^"]*)"[^>]*>/i', $html, $matches)) {
            $metadata['site_name'] = trim($matches[1]);
        }

        return $metadata;
    }

    /**
     * Clean text content
     */
    private function cleanText(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Remove special characters but keep basic punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s\.\,\!\?\-\'"]/u', ' ', $text);

        // Trim and normalize
        $text = trim($text);

        return $text;
    }

    /**
     * Resolve relative URLs
     */
    private function resolveUrl(string $url, string $baseUrl): string
    {
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // Handle relative URLs
        $baseInfo = parse_url($baseUrl);
        if (! $baseInfo) {
            return $url;
        }

        $scheme = $baseInfo['scheme'] ?? 'https';
        $host = $baseInfo['host'] ?? '';

        if (strpos($url, '//') === 0) {
            return $scheme.':'.$url;
        }

        if (strpos($url, '/') === 0) {
            return $scheme.'://'.$host.$url;
        }

        return $url;
    }
}

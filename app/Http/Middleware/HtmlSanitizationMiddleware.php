<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HtmlSanitizationMiddleware
{
    private array $allowedTags = [
        'p', 'br', 'strong', 'em', 'u', 'i', 'b', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
        'ul', 'ol', 'li', 'blockquote', 'code', 'pre', 'a', 'img', 'div', 'span',
        'table', 'thead', 'tbody', 'tr', 'td', 'th', 'caption'
    ];

    private array $allowedAttributes = [
        'href' => ['a'],
        'title' => ['a'],
        'src' => ['img'],
        'alt' => ['img'],
        'width' => ['img', 'table'],
        'height' => ['img'],
        'class' => ['*'],
        'id' => ['*'],
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $this->sanitizeInput($request);
        
        $response = $next($request);

        return $response;
    }

    private function sanitizeInput(Request $request): void
    {
        $input = $request->all();
        
        array_walk_recursive($input, function (&$value, $key) {
            if (is_string($value)) {
                $value = $this->removeDangerousPatterns($value, removeForms: true);
                
                // Sanitize HTML if present
                if ($this->containsHtml($value)) {
                    $value = $this->sanitizeHtml($value);
                }
            }
        });
        
        $request->merge($input);
    }

    private function sanitizeOutput(Response $response): void
    {
        $content = $response->getContent();
        
        if ($content) {
            $content = $this->removeDangerousPatterns($content, removeForms: false, removeScripts: false);
            $response->setContent($content);
        }
    }

    private function removeDangerousPatterns(string $content, bool $removeForms = true, bool $removeScripts = true): string
    {
        $dangerousPatterns = [
            '/on\w+\s*=\s*["\']?[^"\'>]*["\']?/i', // onload, onclick, etc.
            '/javascript\s*:/i',                    // javascript: protocol
            '/data\s*:\s*text\/html/i',              // data: URLs with HTML
            '/vbscript\s*:/i',                       // vbscript: protocol
        ];

        if ($removeScripts) {
            $dangerousPatterns[] = '/<\s*script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script\s*>/is';
            $dangerousPatterns[] = '/<\s*iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe\s*>/is';
            $dangerousPatterns[] = '/<\s*object\b[^<]*(?:(?!<\/object>)<[^<]*)*<\/object\s*>/is';
            $dangerousPatterns[] = '/<\s*embed\b[^<]*(?:(?!<\/embed>)<[^<]*)*<\/embed\s*>/is';
        }

        if ($removeForms) {
            $dangerousPatterns[] = '/<\s*form\b[^<]*(?:(?!<\/form>)<[^<]*)*<\/form\s*>/is';
        }
        
        return preg_replace($dangerousPatterns, '', $content);
    }

    private function sanitizeHtml(string $html): string
    {
        // Use DOMDocument for proper HTML parsing
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        
        // Load HTML with proper encoding
        $dom->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();
        
        // Remove disallowed tags
        $this->removeDisallowedTags($dom);
        
        // Remove disallowed attributes
        $this->removeDisallowedAttributes($dom);
        
        // Clean up URLs in href and src attributes
        $this->sanitizeUrls($dom);
        
        $sanitized = $dom->saveHTML();
        
        // Remove XML encoding declaration if present
        $sanitized = preg_replace('/<\?xml[^>]*>/', '', $sanitized);
        
        return trim($sanitized);
    }

    private function removeDisallowedTags(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        
        // Get all tags
        $allTags = $xpath->query('//*');
        
        foreach ($allTags as $tag) {
            $tagName = strtolower($tag->nodeName);
            
            if (!in_array($tagName, $this->allowedTags)) {
                // Replace with text content
                $fragment = $dom->createDocumentFragment();
                while ($tag->firstChild) {
                    $fragment->appendChild($tag->firstChild);
                }
                $tag->parentNode->replaceChild($fragment, $tag);
            }
        }
    }

    private function removeDisallowedAttributes(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        
        foreach ($this->allowedAttributes as $attribute => $allowedTags) {
            $elements = $xpath->query('//*[@' . $attribute . ']');
            
            foreach ($elements as $element) {
                $tagName = strtolower($element->nodeName);
                
                if (!in_array('*', $allowedTags) && !in_array($tagName, $allowedTags)) {
                    $element->removeAttribute($attribute);
                }
            }
        }
        
        // Remove any attributes not in allowed list
        $allElements = $xpath->query('//*[@*]');
        foreach ($allElements as $element) {
            foreach ($element->attributes as $attr) {
                $attrName = strtolower($attr->nodeName);
                if (!isset($this->allowedAttributes[$attrName]) && 
                    !in_array($attrName, ['class', 'id'])) {
                    $element->removeAttribute($attr->nodeName);
                }
            }
        }
    }

    private function sanitizeUrls(\DOMDocument $dom): void
    {
        $xpath = new \DOMXPath($dom);
        
        // Sanitize href attributes
        $links = $xpath->query('//a[@href]');
        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            $sanitized = $this->sanitizeUrl($href);
            if ($sanitized !== $href) {
                $link->setAttribute('href', $sanitized);
            }
        }
        
        // Sanitize src attributes
        $images = $xpath->query('//img[@src]');
        foreach ($images as $img) {
            $src = $img->getAttribute('src');
            $sanitized = $this->sanitizeUrl($src);
            if ($sanitized !== $src) {
                $img->setAttribute('src', $sanitized);
            }
        }
    }

    private function sanitizeUrl(string $url): string
    {
        // Remove dangerous protocols
        $dangerousProtocols = ['javascript:', 'vbscript:', 'data:', 'file:'];
        
        foreach ($dangerousProtocols as $protocol) {
            if (stripos($url, $protocol) === 0) {
                return '#';
            }
        }
        
        // Ensure URL starts with http/https or is relative
        if (!preg_match('/^(https?:\/\/|\/|#)/i', $url)) {
            return '#';
        }
        
        return $url;
    }

    private function containsHtml(string $string): bool
    {
        return $string !== strip_tags($string);
    }

    private function isHtmlResponse(Response $response): bool
    {
        $contentType = $response->headers->get('Content-Type');
        return $contentType && stripos($contentType, 'text/html') !== false;
    }
}

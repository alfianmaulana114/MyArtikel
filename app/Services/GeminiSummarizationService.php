<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiSummarizationService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent';
    private int $maxRetries = 3;
    private int $retryDelay = 1; // seconds
    
    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
    }

    /**
     * Generate summary using Gemini API
     */
    public function generateSummary(string $content, int $maxWords = 150, string $language = 'id'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $cacheKey = $this->generateCacheKey($content, $maxWords, $language);
        
        // Check cache first
        if ($cachedSummary = Cache::get($cacheKey)) {
            return $cachedSummary;
        }

        $prompt = $this->buildPrompt($content, $maxWords, $language);
        
        $response = $this->makeApiRequest($prompt);
        
        $summary = $this->parseResponse($response);
        
        // Cache the result for 24 hours
        Cache::put($cacheKey, $summary, now()->addHours(24));
        
        return $summary;
    }

    /**
     * Build structured prompt for Gemini
     */
    private function buildPrompt(string $content, int $maxWords, string $language): string
    {
        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';
        
        return "
            Buatlah ringkasan artikel berikut dalam {$langText} dengan struktur yang jelas:
            
            KONTEN ARTIKEL:
            {$content}
            
            INSTRUKSI:
            1. Buat ringkasan dalam bentuk paragraf yang padat dan informatif (maksimal {$maxWords} kata)
            2. Ekstrak 3-5 poin kunci dari artikel ini
            3. Gunakan bahasa yang jelas dan mudah dipahami
            4. Pastikan ringkasan mencakup ide utama dan poin penting
            
            FORMAT OUTPUT (JSON):
            {
                \"summary\": \"Ringkasan dalam paragraf...\",
                \"key_points\": [\"Poin 1\", \"Poin 2\", \"Poin 3\"]
            }
            
            Pastikan output dalam format JSON yang valid dan bisa diparsing.
        ";
    }

    /**
     * Make API request with retry logic
     */
    private function makeApiRequest(string $prompt): array
    {
        $retries = 0;
        
        while ($retries < $this->maxRetries) {
            try {
                $response = Http::timeout(60)
                    ->withHeaders([
                        'Content-Type' => 'application/json',
                    ])
                    ->post($this->apiUrl . '?key=' . $this->apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt]
                                ]
                            ]
                        ],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'topK' => 1,
                            'topP' => 1,
                            'maxOutputTokens' => 2048,
                            'stopSequences' => []
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_MEDIUM_AND_ABOVE'
                            ]
                        ]
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                throw new Exception('API request failed: ' . $response->body());
                
            } catch (Exception $e) {
                $retries++;
                Log::warning("Gemini API request failed (attempt {$retries}): " . $e->getMessage());
                
                if ($retries >= $this->maxRetries) {
                    throw new Exception('Gemini API request failed after ' . $this->maxRetries . ' attempts: ' . $e->getMessage());
                }
                
                sleep($this->retryDelay * $retries);
            }
        }
    }

    /**
     * Parse Gemini API response
     */
    private function parseResponse(array $response): array
    {
        try {
            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Invalid response structure from Gemini API');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            
            // Extract JSON from the response text
            preg_match('/\{[\s\S]*\}/', $text, $matches);
            
            if (empty($matches)) {
                throw new Exception('No JSON found in Gemini API response');
            }

            $jsonData = json_decode($matches[0], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse JSON from Gemini response: ' . json_last_error_msg());
            }

            return [
                'summary' => $jsonData['summary'] ?? $this->extractSummaryFallback($text),
                'key_points' => $jsonData['key_points'] ?? [],
                'raw_response' => $text,
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
                'model' => 'gemini-pro'
            ];
            
        } catch (Exception $e) {
            Log::error('Failed to parse Gemini response: ' . $e->getMessage());
            throw new Exception('Failed to parse Gemini API response: ' . $e->getMessage());
        }
    }

    /**
     * Fallback summary extraction if JSON parsing fails
     */
    private function extractSummaryFallback(string $text): string
    {
        // Try to extract the main content as fallback
        $lines = explode("\n", trim($text));
        $summary = '';
        
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || strpos($line, '{') !== false || strpos($line, '}') !== false) {
                continue;
            }
            
            if (strlen($line) > 50) { // Assume longer lines contain summary content
                $summary .= $line . ' ';
            }
        }
        
        return trim($summary) ?: 'Ringkasan tidak tersedia';
    }

    /**
     * Generate cache key for content
     */
    private function generateCacheKey(string $content, int $maxWords, string $language): string
    {
        $contentHash = md5($content);
        return "gemini_summary:{$contentHash}:{$maxWords}:{$language}";
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
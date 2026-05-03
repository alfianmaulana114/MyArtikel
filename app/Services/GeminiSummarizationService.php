<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiSummarizationService
{
    private string $apiKey;
    private string $apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent';
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
        $topicIndicator = $this->detectTopic($content);
        
        return "Kamu adalah seorang penulis ringkasan artikel profesional yang ahli dalam membuat ringkasan yang informatif dan mudah dipahami.

BUAT RINGKASAN ARTIKEL BERIKUT DALAM {$langText}:

KONTEN ARTIKEL:
{$content}

INSTRUKSI:
1. Buat ringkasan dalam bentuk paragraf yang PADAT dan INFORMATIF (maksimal {$maxWords} kata)
2. Ringkasan harus mencakup:
   - Topik utama artikel ({$topicIndicator})
   - Poin-poin penting dari artikel
   - Kesimpulan atau implikasi dari informasi
3. Ekstrak 3-5 poin kunci yang mewakili ide utama artikel
4. Gunakan bahasa yang jelas, profesional, dan mudah dipahami
5. Pastikan ringkasan COHERENT - kalimat satu terhubung dengan yang lainnya
6. Hindari pengulangan informasi yang sudah disebutkan

FORMAT OUTPUT (WAJIB JSON - tanpa markdown atau penjelasan tambahan):
{
    \"summary\": \"Paragraf ringkasan yang padate dan informatif... (maksimal {$maxWords} kata)\",
    \"key_points\": [\"Poin kunci 1 yang spesifik\", \"Poin kunci 2 yang spesifik\", \"Poin kunci 3 yang spesifik\"]
}";
    }
    
    private function detectTopic(string $content): string
    {
        $content = strtolower(strip_tags($content));
        $words = str_word_count($content, 1);
        $wordFreq = array_count_values($words);
        arsort($wordFreq);

        $stopWords = ['yang', 'untuk', 'dengan', 'tidak', 'dari', 'dalam', 'adalah', 'akan', 'oleh', 'ini', 'itu', 'dan', 'atau', 'jika', 'ters', 'pada', 'untuk', 'dapat', 'sudah', 'saya', 'kami', 'nya', 'lebih', 'juga', 'telah', 'bahwa', 'hanya'];

        $importantWords = [];
        foreach ($wordFreq as $word => $count) {
            if (strlen($word) > 4 && !in_array($word, $stopWords) && is_numeric($count) === false) {
                $importantWords[$word] = $count;
            }
        }

        arsort($importantWords);
        $topWords = array_slice(array_keys($importantWords), 0, 3);
        return !empty($topWords) ? implode(', ', $topWords) : 'artikel';
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
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_NONE'
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_NONE'
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
            
            // Clean the text first - remove markdown code blocks if present
            $cleanText = preg_replace('/```json\s*/', '', $text);
            $cleanText = preg_replace('/```\s*/', '', $cleanText);
            $cleanText = trim($cleanText);
            
            // Try to extract JSON from the response text with improved regex
            preg_match('/\{[\s\S]*\}/', $cleanText, $matches);
            
            if (empty($matches)) {
                throw new Exception('No JSON found in Gemini API response');
            }

            $jsonData = json_decode($matches[0], true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Failed to parse JSON from Gemini response: ' . json_last_error_msg());
            }

            $summary = $jsonData['summary'] ?? $this->extractSummaryFallback($cleanText);
            $keyPoints = $jsonData['key_points'] ?? [];
            
            // Validate and sanitize summary length
            $wordCount = str_word_count($summary);
            if ($wordCount > 300) {
                $summary = implode(' ', array_slice(str_word_count($summary, 1), 0, 300)) . '...';
            }

            return [
                'summary' => $summary,
                'key_points' => is_array($keyPoints) ? array_slice($keyPoints, 0, 5) : [],
                'raw_response' => $text,
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
                'model' => 'gemini-2.5-flash'
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
        return "gemini_summary:v2:{$contentHash}:{$maxWords}:{$language}";
    }

    /**
     * Check if service is available
     */
    public function isAvailable(): bool
    {
        return !empty($this->apiKey);
    }
}
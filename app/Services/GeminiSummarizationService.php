<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Exception;

class GeminiSummarizationService
{
    private string $apiKey;
    private string $apiUrl;
    private int $maxRetries = 3;
    private int $retryDelay = 1; // seconds
    private CitationExtractorService $extractor;
    
    public function __construct()
    {
        $this->apiKey = config('services.gemini.api_key');
        $model = config('services.gemini.model', 'gemini-2.0-flash');
        $this->apiUrl = "https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent";
        $this->extractor = new CitationExtractorService();
    }

    /**
     * Generate summary using Gemini API
     */
    public function generateSummary(string $content, int $maxWords = 150, string $language = 'id', ?string $researchTitle = null): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $cacheKey = $this->generateCacheKey($content, $maxWords, $language);
        
        // Check cache first
        if ($cachedSummary = Cache::get($cacheKey)) {
            return $cachedSummary;
        }

        $prompt = $this->buildPrompt($this->preFilter($content, $researchTitle), $maxWords, $language, $researchTitle);
        
        $response = $this->makeApiRequest($prompt);
        
        $summary = $this->parseResponse($response);
        
        // Cache the result for 24 hours
        Cache::put($cacheKey, $summary, now()->addHours(24));
        
        return $summary;
    }

    /**
     * Generate research citations/quotation suggestions
     */
    public function generateResearchCitations(string $content, string $researchTitle, string $language = 'id', ?string $researchContext = null): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $cacheKey = 'gemini_citations:v2:' . md5($content . $researchTitle . ($researchContext ?? ''));

        if ($cached = Cache::get($cacheKey)) {
            if (isset($cached['success']) && $cached['success'] === true) {
                return $cached;
            }
        }

        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $preFiltered = $this->preFilter($content, $researchTitle, $researchContext);

        if (empty(trim($preFiltered))) {
            return [
                'success' => false,
                'error' => 'Konten artikel kosong, tidak bisa membuat kutipan.',
                'citations' => [],
            ];
        }

        $prompt = $this->buildCitationPrompt($preFiltered, $researchTitle, $language, $researchContext);

        try {
            $response = $this->makeApiRequest($prompt, 2048);

            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                Log::error('Gemini citation: invalid response structure', [
                    'finish_reason' => $finishReason,
                    'response_keys' => array_keys($response),
                ]);

                if ($finishReason === 'SAFETY' || $finishReason === 'RECITATION') {
                    Log::warning('Gemini citation: blocked by safety, using local fallback');
                    return $this->localCitationFallback($content, $researchTitle);
                }

                throw new Exception('Gemini tidak merespons dengan benar (finishReason: ' . $finishReason . ')');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            Log::info('Gemini citation raw response', ['text_preview' => mb_substr($text, 0, 300)]);

            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData === null) {
                Log::error('Gemini citation: no JSON found', ['raw_text' => mb_substr($text, 0, 2000)]);
                return $this->localCitationFallback($content, $researchTitle);
            }

            $citations = $jsonData['citations'] ?? [];

            if (!is_array($citations) || empty($citations)) {
                Log::warning('Gemini citation: empty or invalid citations array', ['data' => $jsonData]);
                return $this->localCitationFallback($content, $researchTitle);
            }

            $result = [
                'success' => true,
                'citations' => array_slice($citations, 0, 5),
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;

        } catch (Exception $e) {
            Log::error('Gemini citation failed, using local fallback: ' . $e->getMessage());
            return $this->localCitationFallback($content, $researchTitle);
        }
    }

    private function localCitationFallback(string $content, string $researchTitle, ?string $researchContext = null): array
    {
        Log::info('Using local citation fallback', ['research_title' => $researchTitle, 'research_context' => $researchContext]);

        $topic = implode(' ', array_filter([$researchTitle, $researchContext]));
        $text = $content;
        $relevant = $this->extractor->extractRelevantChunks($text, $topic, maxChars: 4000);

        $positionMap = [
            'Bab 1' => 'latar_belakang',
            'Bab 2' => 'tinjauan_pustaka',
            'Bab 3' => 'metodologi',
            'Bab 4' => 'pembahasan',
            'Bab 5' => 'kesimpulan',
            'Landasan Teori' => 'tinjauan_pustaka',
            'Kerangka Pemikiran' => 'tinjauan_pustaka',
            'Analisis Data' => 'pembahasan',
        ];

        $defaultPosition = 'pembahasan';
        if ($researchContext) {
            foreach ($positionMap as $key => $pos) {
                if (str_contains($researchContext, $key)) {
                    $defaultPosition = $pos;
                    break;
                }
            }
        }

        $sentences = preg_split('/(?<=[.!?])\s+/', $relevant, -1, PREG_SPLIT_NO_EMPTY);
        $citations = [];
        $positions = ['latar_belakang', 'tinjauan_pustaka', 'metodologi', 'pembahasan', 'kesimpulan'];

        foreach ($sentences as $i => $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) < 30 || mb_strlen($sentence) > 500) {
                continue;
            }
            if (count($citations) >= 5) {
                break;
            }
            $position = $defaultPosition;
            if ($i === 0) {
                $position = $positions[0];
            } elseif ($i <= 1) {
                $position = $positions[1];
            }
            $citations[] = [
                'quote' => $sentence,
                'relevance' => 'Ditemukan relevan dengan topik: ' . mb_strimwidth($topic, 0, 80, '…') . ' (ekstraksi lokal).',
                'position' => $position,
            ];
        }

        if (empty($citations)) {
            return [
                'success' => false,
                'error' => 'Tidak ditemukan kutipan relevan. Coba ubah judul penelitian atau pastikan artikel sudah diproses.',
                'citations' => [],
            ];
        }

        return [
            'success' => true,
            'citations' => $citations,
        ];
    }

    /**
     * Pre-filter content using local extractor to reduce tokens
     */
    private function preFilter(string $content, ?string $researchTitle, ?string $researchContext = null): string
    {
        $topic = implode(' ', array_filter([$researchTitle, $researchContext]));
        if (empty($topic)) {
            return mb_substr($content, 0, 4000);
        }
        return $this->extractor->extractRelevantChunks($content, $topic, maxChars: 4000);
    }

    /**
     * Build citation prompt with pre-filtered content
     */
    private function buildCitationPrompt(string $content, string $researchTitle, string $language, ?string $researchContext = null): string
    {
        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $contextHint = '';
        if (!empty($researchContext)) {
            $contextHint = "\nContext: This is for \"{$researchContext}\" section of a research paper.";
        }

        return <<<PROMPT
You are a research assistant. Given the text below, find 3 verbatim quotes relevant to: "{$researchTitle}"{$contextHint}

For each quote provide:
- quote: exact verbatim from the text
- relevance: brief explanation (in {$langText})
- position: one of latar_belakang, tinjauan_pustaka, metodologi, pembahasan, kesimpulan

IMPORTANT: Respond with ONLY a valid JSON object, no other text before or after.

{"citations":[{"quote":"...","relevance":"...","position":"..."}]}

Text:
{$content}
PROMPT;
    }

    /**
     * Build structured prompt for Gemini (token-optimized)
     */
    private function buildPrompt(string $content, int $maxWords, string $language, ?string $researchTitle = null): string
    {
        $lang = $language === 'id' ? 'ID' : 'EN';
        
        $researchHint = '';
        if (!empty($researchTitle)) {
            $researchHint = "\nFokus riset: \"{$researchTitle}\"\n";
        }

        return <<<PROMPT
Summarize the following article in {$lang}, maximum {$maxWords} words, with 3-5 key points.
{$researchHint}
Text:
{$content}

IMPORTANT: Respond with ONLY a valid JSON object, no other text before or after.

{"summary":"...","key_points":["...","..."]}
PROMPT;
    }
    
    
    /**
     * Make API request with retry logic
     */
    private function makeApiRequest(string $prompt, int $maxOutputTokens = 2048): array
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
                            'maxOutputTokens' => $maxOutputTokens,
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
                $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                throw new Exception('Invalid response structure from Gemini API (finishReason: ' . $finishReason . ')');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];

            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData !== null) {
                $summary = $jsonData['summary'] ?? $this->extractSummaryFallback($text);
                $keyPoints = $jsonData['key_points'] ?? [];

                $wordCount = str_word_count($summary);
                if ($wordCount > 300) {
                    $summary = implode(' ', array_slice(str_word_count($summary, 1), 0, 300)) . '...';
                }

                return [
                    'summary' => $summary,
                    'key_points' => is_array($keyPoints) ? array_slice($keyPoints, 0, 5) : [],
                    'raw_response' => $text,
                    'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
                    'model' => 'gemini-2.5-flash',
                ];
            }

            $summary = $this->extractSummaryFallback($text);
            return [
                'summary' => $summary,
                'key_points' => [],
                'raw_response' => $text,
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
                'model' => 'gemini-2.5-flash',
            ];

        } catch (Exception $e) {
            Log::error('Failed to parse Gemini response: ' . $e->getMessage());
            throw new Exception('Failed to parse Gemini API response: ' . $e->getMessage());
        }
    }

    private function extractJsonFromText(string $text): ?array
    {
        $cleanText = preg_replace('/```json\s*/i', '', $text);
        $cleanText = preg_replace('/```\s*/', '', $cleanText);
        $cleanText = trim($cleanText);

        if (preg_match('/\{([^{}]|(?R))*\}/s', $cleanText, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        if (preg_match('/\{[\s\S]*\}/', $cleanText, $matches)) {
            $candidate = $matches[0];
            $lastBrace = strrpos($candidate, '}');
            while ($lastBrace !== false) {
                $sub = substr($candidate, 0, $lastBrace + 1);
                if (preg_match('/\{.*\}/s', $sub)) {
                    $decoded = json_decode($sub, true);
                    if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                        return $decoded;
                    }
                }
                $lastBrace = strrpos(substr($candidate, 0, $lastBrace), '}');
            }
        }

        $decoded = json_decode($cleanText, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        return null;
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

    /**
     * Generate content with custom prompt (for outline/draft generation)
     * Token-efficient with retry logic
     */
    public function generateWithPrompt(string $prompt, array $options = []): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $maxTokens = $options['max_tokens'] ?? 1024;
        $temperature = $options['temperature'] ?? 0.3;
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
                            'temperature' => $temperature,
                            'topK' => 1,
                            'topP' => 1,
                            'maxOutputTokens' => $maxTokens,
                        ],
                        'safetySettings' => [
                            ['category' => 'HARM_CATEGORY_HARASSMENT', 'threshold' => 'BLOCK_NONE'],
                            ['category' => 'HARM_CATEGORY_HATE_SPEECH', 'threshold' => 'BLOCK_NONE'],
                            ['category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT', 'threshold' => 'BLOCK_NONE'],
                            ['category' => 'HARM_CATEGORY_DANGEROUS_CONTENT', 'threshold' => 'BLOCK_NONE'],
                        ]
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (!isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        throw new Exception('Invalid response structure');
                    }

                    return [
                        'success' => true,
                        'content' => $data['candidates'][0]['content']['parts'][0]['text'],
                        'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                    ];
                }

                throw new Exception('API request failed: ' . $response->body());

            } catch (Exception $e) {
                $retries++;
                Log::warning("Gemini generateWithPrompt failed (attempt {$retries}): " . $e->getMessage());

                if ($retries >= $this->maxRetries) {
                    Log::error('Gemini generateWithPrompt failed after ' . $this->maxRetries . ' attempts');
                    return [
                        'success' => false,
                        'error' => $e->getMessage(),
                    ];
                }

                sleep($this->retryDelay * $retries);
            }
        }
    }
}
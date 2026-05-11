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

        $prompt = $this->buildPrompt($content, $maxWords, $language, $researchTitle);
        
        $response = $this->makeApiRequest($prompt);
        
        $summary = $this->parseResponse($response);
        
        // Cache the result for 24 hours
        Cache::put($cacheKey, $summary, now()->addHours(24));
        
        return $summary;
    }

    /**
     * Generate research citations/quotation suggestions
     */
    public function generateResearchCitations(string $content, string $researchTitle, string $language = 'id'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $cacheKey = 'gemini_citations:' . md5($content . $researchTitle);

        if ($cached = Cache::get($cacheKey)) {
            return $cached;
        }

        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        // Limit content to avoid token overflow, keep enough for meaningful quotes
        $maxContentLen = 15000;
        $truncatedContent = mb_strlen($content) > $maxContentLen
            ? mb_substr($content, 0, $maxContentLen) . '...'
            : $content;

        $prompt = "Anda adalah asisten riset tingkat lanjut yang ahli dalam analisis literatur akademik.

Berikut adalah teks jurnal/artikel (mungkin terpotong):

\"\"\"
{$truncatedContent}
\"\"\"

Topik/Judul penelitian user: \"{$researchTitle}\"

Tugas Anda:
1. Carikan 3 kutipan PERSIS (kata per kata, verbatim) dari teks jurnal di atas yang sangat relevan dengan penelitian user berjudul \"{$researchTitle}\".
2. Untuk setiap kutipan, berikan:
   - \"quote\": Kalimat/kutipan persis persis dari teks (jangan diparafrase, harus verbatim).
   - \"relevance\": Penjelasan SINGKAT dalam {$langText} mengapa kutipan tersebut cocok.
   - \"position\": Di bagian mana kutipan ini paling cocok digunakan (latar_belakang / tinjauan_pustaka / metodologi / pembahasan / kesimpulan).

PENTING: Hanya output JSON. Jangan tambahkan markdown, penjelasan, atau teks apapun selain JSON.

{
    \"citations\": [
        {
            \"quote\": \"...\",
            \"relevance\": \"...\",
            \"position\": \"...\"
        }
    ]
}";

        $response = $this->makeApiRequest($prompt, 4096);

        try {
            if (!isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                Log::error('Gemini citation: invalid response structure', ['response' => $response]);
                throw new Exception('Invalid response structure from Gemini API');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];

            // Clean markdown and extract JSON
            $cleanText = preg_replace('/```json\s*/i', '', $text);
            $cleanText = preg_replace('/```\s*/', '', $cleanText);
            $cleanText = trim($cleanText);

            // Try multiple regex patterns to find JSON
            $jsonStr = null;
            if (preg_match('/\{[\s\S]*\}/', $cleanText, $matches)) {
                $jsonStr = $matches[0];
            }

            if (!$jsonStr) {
                Log::error('Gemini citation: no JSON found', ['raw_text' => $text]);
                throw new Exception('No JSON found in Gemini API response');
            }

            $jsonData = json_decode($jsonStr, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                Log::error('Gemini citation: JSON parse failed', [
                    'json_error' => json_last_error_msg(),
                    'json_str' => mb_substr($jsonStr, 0, 500),
                ]);
                throw new Exception('Failed to parse JSON from Gemini response: ' . json_last_error_msg());
            }

            $citations = $jsonData['citations'] ?? [];

            $result = [
                'success' => true,
                'citations' => array_slice($citations, 0, 5),
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;

        } catch (Exception $e) {
            Log::error('Failed to parse Gemini citation response: ' . $e->getMessage());
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'citations' => [],
            ];
        }
    }

    /**
     * Build structured prompt for Gemini
     */
    private function buildPrompt(string $content, int $maxWords, string $language, ?string $researchTitle = null): string
    {
        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';
        $topicIndicator = $this->detectTopic($content);

        $researchSection = '';
        if (!empty($researchTitle)) {
            $researchSection = "\nKONTEKS RISET:\nTopik/Judul penelitian user adalah: \"{$researchTitle}\"\n\nPastikan ringkasan menyoroti bagian-bagian yang relevan dengan judul penelitian tersebut.\n";
        }

        return "Kamu adalah seorang penulis ringkasan artikel profesional yang ahli dalam membuat ringkasan yang informatif dan mudah dipahami.

BUAT RINGKASAN ARTIKEL BERIKUT DALAM {$langText}:

KONTEN ARTIKEL:
{$content}
{$researchSection}
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
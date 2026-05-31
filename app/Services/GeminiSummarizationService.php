<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

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
        $this->extractor = new CitationExtractorService;
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

        $cacheKey = 'gemini_citations:v5:'.md5($content.$researchTitle.($researchContext ?? ''));

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
            $response = $this->makeApiRequest($prompt, 4096);

            if (! isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                Log::error('Gemini citation: invalid response structure', [
                    'finish_reason' => $finishReason,
                    'response_keys' => array_keys($response),
                ]);

                if ($finishReason === 'SAFETY' || $finishReason === 'RECITATION') {
                    Log::warning('Gemini citation: blocked by safety, using local fallback');

                    return $this->localCitationFallback($content, $researchTitle);
                }

                throw new Exception('Gemini tidak merespons dengan benar (finishReason: '.$finishReason.')');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            Log::info('Gemini citation raw response', ['text_preview' => mb_substr($text, 0, 300)]);

            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData === null) {
                Log::error('Gemini citation: no JSON found', ['raw_text' => mb_substr($text, 0, 2000)]);

                return $this->localCitationFallback($content, $researchTitle, $researchContext);
            }

            $citations = $jsonData['citations'] ?? [];

            if (! is_array($citations) || empty($citations)) {
                Log::warning('Gemini citation: empty or invalid citations array', ['data' => $jsonData]);

                return $this->localCitationFallback($content, $researchTitle, $researchContext);
            }

            // Normalize and trim citations
            $citations = array_slice($citations, 0, 5);

            // Validate that quotes actually exist in the source text (check full content)
            $citations = $this->validateCitations($citations, $content);

            if (empty($citations)) {
                Log::warning('Gemini citation: all citations failed validation, using local fallback');

                return $this->localCitationFallback($content, $researchTitle, $researchContext);
            }

            // Generate paraphrases for any citations missing them
            $missingParaphrases = [];
            foreach ($citations as $idx => $citation) {
                if (empty($citation['paraphrase'])) {
                    $missingParaphrases[] = $idx;
                }
            }

            if (! empty($missingParaphrases)) {
                $batchResult = $this->generateParaphrasesBatch(
                    array_map(fn ($idx) => $citations[$idx]['quote'], $missingParaphrases),
                    $researchTitle,
                    $researchContext,
                    $language
                );
                if ($batchResult['success']) {
                    foreach ($missingParaphrases as $i => $idx) {
                        $citations[$idx]['paraphrase'] = $batchResult['paraphrases'][$i] ?? null;
                    }
                } else {
                    // Fallback: generate paraphrases individually per quote
                    foreach ($missingParaphrases as $idx) {
                        $singleResult = $this->paraphraseQuote(
                            $citations[$idx]['quote'],
                            $researchTitle,
                            $researchContext,
                            $language
                        );
                        if ($singleResult['success']) {
                            $citations[$idx]['paraphrase'] = $singleResult['paraphrase'];
                        }
                    }
                }
            }

            // Ensure all citations have a paraphrase key (null if truly unavailable)
            foreach ($citations as $idx => $citation) {
                if (! isset($citation['paraphrase']) || empty(trim($citation['paraphrase']))) {
                    $citations[$idx]['paraphrase'] = null;
                }
            }

            // Override position based on research context so all citations align
            $forcedPosition = $this->resolvePositionFromContext($researchContext);
            foreach ($citations as $idx => $citation) {
                $citations[$idx]['position'] = $forcedPosition;
                $citations[$idx]['generated_for_title'] = $researchTitle;
                $citations[$idx]['generated_for_context'] = $researchContext;
                $citations[$idx]['generated_at'] = now()->toDateTimeString();
            }

            $result = [
                'success' => true,
                'citations' => $citations,
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;

        } catch (Exception $e) {
            Log::error('Gemini citation failed, using local fallback: '.$e->getMessage());

            return $this->localCitationFallback($content, $researchTitle, $researchContext);
        }
    }

    private function localCitationFallback(string $content, string $researchTitle, ?string $researchContext = null): array
    {
        Log::info('Using local citation fallback', ['research_title' => $researchTitle, 'research_context' => $researchContext]);

        // Build topic string with contextual keywords for better relevance
        $topicParts = array_filter([$researchTitle, $researchContext]);
        $topic = implode(' ', $topicParts);
        $contextualKeywords = $this->buildContextualKeywords($researchContext);
        if (! empty($contextualKeywords)) {
            $topic .= ' ' . implode(' ', $contextualKeywords);
        }

        $relevant = $this->extractor->extractRelevantChunks($content, $topic, maxChars: 5000);

        $forcedPosition = $this->resolvePositionFromContext($researchContext);

        $sentences = preg_split('/(?<=[.!?])\s+/', $relevant, -1, PREG_SPLIT_NO_EMPTY);

        // Score each sentence by: (1) title keyword match, (2) section-type keyword match
        $titleWords = array_filter(array_map('mb_strtolower', preg_split('/\s+/', $researchTitle)));
        $sectionTypeKeywords = $this->buildContextualKeywords($researchContext);
        $contextWords = $researchContext
            ? array_filter(array_map('mb_strtolower', preg_split('/\s+/', $researchContext)))
            : [];

        $scored = [];
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (mb_strlen($sentence) < 40 || mb_strlen($sentence) > 700) {
                continue;
            }
            $lower = mb_strtolower($sentence);
            $score = 0;

            // Title keyword score (weighted highest)
            foreach ($titleWords as $tw) {
                if (mb_strlen($tw) > 3 && str_contains($lower, $tw)) {
                    $score += 2;
                }
            }

            // Section-type semantic keyword score
            foreach ($sectionTypeKeywords as $kw) {
                if (str_contains($lower, mb_strtolower($kw))) {
                    $score += 1.5;
                }
            }

            // Context word score (e.g. words from "Bab 2 — Tinjauan Pustaka")
            foreach ($contextWords as $cw) {
                if (mb_strlen($cw) > 3 && str_contains($lower, $cw)) {
                    $score += 0.5;
                }
            }

            $scored[] = ['sentence' => $sentence, 'score' => $score];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        $citations = [];
        foreach ($scored as $item) {
            $sentence = $item['sentence'];
            if (count($citations) >= 5) {
                break;
            }

            $localParaphrase = $this->generateLocalParaphrase($sentence);

            $relevanceParts = ["Kalimat ini mendukung penelitian '{$researchTitle}'"];
            if ($researchContext) {
                $relevanceParts[] = "dan relevan untuk bagian {$researchContext}";
            }
            $relevanceText = implode(' ', $relevanceParts) . '.';

            $citations[] = [
                'quote'                  => $sentence,
                'paraphrase'             => $localParaphrase,
                'relevance'              => $relevanceText,
                'position'               => $forcedPosition,
                'generated_for_title'    => $researchTitle,
                'generated_for_context'  => $researchContext,
                'generated_at'           => now()->toDateTimeString(),
            ];
        }

        if (empty($citations)) {
            return [
                'success' => false,
                'error'   => 'Tidak ditemukan kutipan relevan. Coba ubah judul penelitian atau pastikan artikel sudah diproses.',
                'citations' => [],
            ];
        }

        return [
            'success'  => true,
            'citations' => $citations,
        ];
    }

    /**
     * Paraphrase a quote while preserving meaning and academic tone.
     */
    public function paraphraseQuote(string $quote, string $researchTitle, ?string $researchContext = null, string $language = 'id'): array
    {
        if (empty($this->apiKey)) {
            throw new Exception('Gemini API key is not configured');
        }

        $cacheKey = 'gemini_paraphrase:v1:'.md5($quote.$researchTitle.($researchContext ?? '').$language);

        if ($cached = Cache::get($cacheKey)) {
            if (isset($cached['success']) && $cached['success'] === true) {
                return $cached;
            }
        }

        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $contextHint = '';
        if (! empty($researchContext)) {
            $safeCtx = str_replace('"', '\\"', $researchContext);
            $contextHint = " Konteks riset: \"{$safeCtx}\".";
        }

        $safeTitle = str_replace('"', '\\"', $researchTitle);
        $safeQuote = str_replace('"', '\\"', $quote);

        $prompt = <<<PROMPT
You are an academic writing assistant. Rephrase the following quote in {$langText} so that it retains the original meaning but uses different words and sentence structure. The paraphrase should sound natural, maintain academic tone, and avoid simply swapping synonyms. Keep the length roughly similar to the original.

Research context: "{$safeTitle}".{$contextHint}

Original quote:
"{$safeQuote}"

IMPORTANT: Respond with ONLY a valid JSON object, no other text before or after.

{"paraphrase":"..."}
PROMPT;

        try {
            $response = $this->makeApiRequest($prompt, 1024);

            if (! isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                throw new Exception('Gemini tidak merespons dengan benar (finishReason: '.$finishReason.')');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            Log::info('Gemini paraphrase raw response', ['text_preview' => mb_substr($text, 0, 300)]);

            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData === null || empty($jsonData['paraphrase'])) {
                Log::error('Gemini paraphrase: no JSON or paraphrase found', ['raw_text' => mb_substr($text, 0, 2000)]);

                return [
                    'success' => false,
                    'error' => 'Gagal memparafrase kutipan. Coba lagi nanti.',
                ];
            }

            $result = [
                'success' => true,
                'paraphrase' => $jsonData['paraphrase'],
                'tokens_used' => $response['usageMetadata']['totalTokenCount'] ?? 0,
            ];

            Cache::put($cacheKey, $result, now()->addHours(24));

            return $result;

        } catch (Exception $e) {
            Log::error('Gemini paraphrase failed: '.$e->getMessage());

            return [
                'success' => false,
                'error' => 'Gagal memparafrase kutipan: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Generate paraphrases for multiple quotes in a single API call.
     */
    private function generateParaphrasesBatch(array $quotes, string $researchTitle, ?string $researchContext = null, string $language = 'id'): array
    {
        if (empty($quotes)) {
            return ['success' => true, 'paraphrases' => []];
        }

        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $contextHint = '';
        if (! empty($researchContext)) {
            $safeCtx = str_replace('"', '\\"', $researchContext);
            $contextHint = " Konteks riset: \"{$safeCtx}\".";
        }

        $safeTitle = str_replace('"', '\\"', $researchTitle);
        $safeQuotes = array_map(fn ($q) => str_replace('"', '\\"', $q), $quotes);
        $quotesText = implode("\n---\n", array_map(fn ($q, $i) => 'Quote '.($i + 1).': "'.$q.'"', $safeQuotes, array_keys($safeQuotes)));

        $prompt = <<<PROMPT
You are an academic writing assistant. Rephrase each of the following quotes in {$langText} so that each retains the original meaning but uses different words and sentence structure. Maintain academic tone and avoid simple synonym swapping.

Research context: "{$safeTitle}".{$contextHint}

{$quotesText}

For each quote, provide a paraphrase in the same order.

IMPORTANT: Respond with ONLY a valid JSON object, no other text before or after.

{"paraphrases":["...","...",...]}
PROMPT;

        try {
            $response = $this->makeApiRequest($prompt, 2048);

            if (! isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                throw new Exception('Gemini tidak merespons dengan benar');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];
            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData === null || ! isset($jsonData['paraphrases']) || ! is_array($jsonData['paraphrases'])) {
                Log::error('Gemini batch paraphrase: invalid response', ['raw_text' => mb_substr($text, 0, 2000)]);

                return ['success' => false, 'paraphrases' => []];
            }

            return [
                'success' => true,
                'paraphrases' => array_slice($jsonData['paraphrases'], 0, count($quotes)),
            ];

        } catch (Exception $e) {
            Log::error('Gemini batch paraphrase failed: '.$e->getMessage());

            return ['success' => false, 'paraphrases' => []];
        }
    }

    /**
     * Generate a simple local paraphrase by restructuring sentence basics.
     * This is a lightweight fallback when AI paraphrasing is unavailable.
     */
    private function generateLocalParaphrase(string $sentence): ?string
    {
        if (empty($sentence)) {
            return null;
        }

        $lower = mb_strtolower($sentence);

        // Passive / active voice flips and common academic restructurings
        $replacements = [
            '/\bmenunjukkan\s+bahwa\b/u' => 'hasil menunjukkan',
            '/\bterdapat\b/u' => 'ditemukan adanya',
            '/\bdapat\s+dilihat\s+bahwa\b/u' => 'terlihat bahwa',
            '/\bsecara\s+signifikan\b/u' => 'dengan signifikan',
            '/\bberdasarkan\s+hasil\b/u' => 'dari hasil yang diperoleh',
            '/\bhasil\s+penelitian\s+menunjukkan\b/u' => 'penelitian ini menemukan bahwa',
            '/\bterdapat\s+pengaruh\b/u' => 'pengaruh tersebut teridentifikasi',
            '/\bdapat\s+disimpulkan\b/u' => 'simpulan yang dapat diambil',
            '/\bterjadi\s+peningkatan\b/u' => 'peningkatan terjadi',
            '/\bterjadi\s+penurunan\b/u' => 'penurunan terjadi',
            '/\bterdapat\s+hubungan\b/u' => 'hubungan ditemukan',
        ];

        $paraphrase = $sentence;
        foreach ($replacements as $pattern => $replacement) {
            if (preg_match($pattern, $lower)) {
                $paraphrase = preg_replace($pattern, $replacement, $paraphrase);
                break; // Only apply one transformation to keep it natural
            }
        }

        // If no pattern matched, try simple sentence restructuring
        if ($paraphrase === $sentence) {
            // Try to move the clause around for common Indonesian structures
            if (preg_match('/^(.+?)\s+(menunjukkan|menemukan|mengungkapkan|meyakinkan)\s+bahwa\s+(.+)$/u', $sentence, $matches)) {
                $paraphrase = $matches[3].' '.$matches[2].' oleh '.$matches[1];
            } elseif (preg_match('/^(.+?)\s+(adalah|merupakan)\s+(.+)$/u', $sentence, $matches)) {
                $paraphrase = $matches[3].' '.$matches[2].' '.$matches[1];
            }
        }

        $paraphrase = trim($paraphrase);
        if ($paraphrase === $sentence || mb_strlen($paraphrase) < 20) {
            return null;
        }

        return $paraphrase;
    }

    /**
     * Pre-filter content using local extractor to reduce tokens.
     * Supplements topic keywords with section-type semantic keywords so the
     * chunk sent to Gemini is already biased toward the right kind of sentences.
     */
    private function preFilter(string $content, ?string $researchTitle, ?string $researchContext = null): string
    {
        $topicParts = array_filter([$researchTitle, $researchContext]);
        $topic = implode(' ', $topicParts);

        // Add section-type keywords so the extractor returns sentences
        // that are semantically appropriate for the requested chapter/section
        $contextualKeywords = $this->buildContextualKeywords($researchContext);
        if (! empty($contextualKeywords)) {
            $topic .= ' ' . implode(' ', $contextualKeywords);
        }

        if (empty(trim($topic))) {
            return mb_substr($content, 0, 4000);
        }

        return $this->extractor->extractRelevantChunks($content, $topic, maxChars: 6000);
    }

    /**
     * Return a list of generic section-type keywords to supplement the topic
     * so the pre-filter chunk contains the right type of sentences.
     */
    private function buildContextualKeywords(?string $researchContext): array
    {
        if (empty($researchContext)) {
            return [];
        }

        $ctx = mb_strtolower($researchContext);

        if ($this->ctxContains($ctx, ['bab 1', 'pendahuluan', 'latar belakang', 'identifikasi masalah'])) {
            return ['masalah', 'fenomena', 'kondisi', 'fakta', 'data', 'tren', 'urgensi', 'gap', 'kesenjangan', 'permasalahan'];
        }

        if ($this->ctxContains($ctx, ['bab 2', 'tinjauan pustaka', 'landasan teori', 'kerangka pemikiran', 'literature review', 'kajian pustaka'])) {
            return ['definisi', 'pengertian', 'teori', 'konsep', 'menurut', 'pendapat', 'para ahli', 'dimensi', 'indikator', 'variabel', 'karakteristik', 'konstruk'];
        }

        if ($this->ctxContains($ctx, ['bab 3', 'metodologi', 'metode', 'research method', 'desain penelitian', 'instrumen', 'teknik pengumpulan'])) {
            return ['metode', 'pendekatan', 'desain', 'instrumen', 'teknik', 'prosedur', 'pengumpulan data', 'validitas', 'reliabilitas', 'populasi', 'sampel', 'kualitatif', 'kuantitatif'];
        }

        if ($this->ctxContains($ctx, ['bab 4', 'pembahasan', 'hasil', 'analisis data', 'result', 'discussion', 'findings', 'temuan'])) {
            return ['hasil', 'temuan', 'menunjukkan', 'ditemukan', 'analisis', 'pengaruh', 'korelasi', 'signifikan', 'terbukti', 'implikasi', 'interpretasi', 'menunjukkan bahwa'];
        }

        if ($this->ctxContains($ctx, ['bab 5', 'kesimpulan', 'simpulan', 'conclusion', 'saran', 'rekomendasi', 'implikasi'])) {
            return ['kesimpulan', 'simpulan', 'disimpulkan', 'saran', 'rekomendasi', 'kontribusi', 'keterbatasan', 'implikasi', 'menyimpulkan'];
        }

        return [];
    }

    /**
     * Build citation prompt with pre-filtered content.
     * Uses a pragmatic "best effort" approach: Gemini is asked to pick the most
     * relevant available quotes rather than returning empty when nothing is perfect.
     * Section-specific guidance tells Gemini what KIND of sentence fits each chapter.
     */
    private function buildCitationPrompt(string $content, string $researchTitle, string $language, ?string $researchContext = null): string
    {
        $langText = $language === 'id' ? 'Bahasa Indonesia' : 'English';

        $safeTitle   = str_replace('"', '\\"', $researchTitle);
        $safeContext = $researchContext ? str_replace('"', '\\"', $researchContext) : '';

        // Resolve forced position
        $forcedPosition = $this->resolvePositionFromContext($researchContext);

        // Section-specific sentence-type guidance
        $sectionGuidance = $this->buildSectionGuidance($researchContext, $researchTitle, $langText);

        // Build context block
        if (! empty($safeContext)) {
            $contextBlock = <<<CTX

KONTEKS PENEMPATAN: Kutipan ini untuk bagian "{$safeContext}" dari penelitian "{$safeTitle}".
Posisi wajib untuk semua kutipan: "{$forcedPosition}".

{$sectionGuidance}
CTX;
        } else {
            $contextBlock = "\nPilih kutipan yang paling relevan dengan topik penelitian: \"{$safeTitle}\".";
        }

        return <<<PROMPT
Kamu adalah asisten riset akademik. Tugasmu: pilih hingga 5 kutipan verbatim dari teks sumber di bawah yang PALING RELEVAN dengan penelitian berikut.

Judul penelitian: "{$safeTitle}"
{$contextBlock}

ATURAN KUTIPAN:
1. quote — Salin teks PERSIS kata per kata dari sumber. Harus ada di dalam teks sumber. Panjang: 1–3 kalimat (40–600 karakter).
2. paraphrase — Tulis ulang dalam {$langText} dengan kata-kata berbeda tapi makna sama. Pertahankan panjang dan gaya akademik.
3. relevance — Jelaskan dalam {$langText} mengapa kutipan ini relevan untuk bagian "{$safeContext}" dari penelitian "{$safeTitle}". Sebutkan konsep atau variabel spesifik yang terhubung.
4. position — Isi dengan nilai tetap "{$forcedPosition}" untuk semua kutipan.

PRIORITAS SELEKSI (urutkan dari yang paling penting):
- Kalimat yang menyebut konsep, variabel, atau istilah yang ada dalam judul penelitian
- Kalimat yang sesuai dengan jenis bagian yang diminta (lihat panduan di atas)
- Kalimat yang bisa dijadikan dasar argumen akademik (bukan kalimat transisi atau generik)

PENTING:
- Jika tidak ada kutipan yang sempurna, pilih yang PALING MENDEKATI relevan — jangan kembalikan array kosong.
- Kutipan harus muncul verbatim di teks sumber (tidak boleh dikarang).
- Field position HARUS selalu "{$forcedPosition}" untuk semua kutipan.
- Balas HANYA dengan JSON valid, tidak ada teks lain.

{"citations":[{"quote":"...","paraphrase":"...","relevance":"...","position":"{$forcedPosition}"}]}

Teks sumber:
{$content}
PROMPT;
    }

    /**
     * Build section-specific guidance text that tells Gemini what KIND of
     * sentences are appropriate for the requested chapter/section.
     */
    private function buildSectionGuidance(  ?string $researchContext, string $researchTitle, string $langText): string
    {
        if (empty($researchContext)) {
            return '';
        }

        $ctx = mb_strtolower($researchContext);

        // Bab 1 / Pendahuluan / Latar Belakang
        if ($this->ctxContains($ctx, ['bab 1', 'pendahuluan', 'latar belakang', 'identifikasi masalah'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK BAB 1 — LATAR BELAKANG
Cari kalimat yang:
- Menggambarkan kondisi, fenomena, atau masalah aktual yang melatarbelakangi isu terkait "{$researchTitle}"
- Menyebutkan fakta empiris, data statistik, atau tren yang memperkuat urgensi penelitian
- Menunjukkan kesenjangan (gap) antara kondisi ideal dan kondisi nyata
- Bersifat kontekstual dan membangun argumentasi mengapa topik ini penting diteliti
HINDARI: definisi konsep murni, langkah metodologi, atau hasil temuan eksperimen.
GUIDE;
        }

        // Bab 2 / Tinjauan Pustaka / Landasan Teori / Kerangka Pemikiran
        if ($this->ctxContains($ctx, ['bab 2', 'tinjauan pustaka', 'landasan teori', 'kerangka pemikiran', 'literature review', 'kajian pustaka'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK BAB 2 — TINJAUAN PUSTAKA / LANDASAN TEORI
Cari kalimat yang:
- Mendefinisikan konsep, variabel, atau teori yang relevan dengan "{$researchTitle}" secara akademik
- Menjelaskan hubungan antar variabel atau kerangka teori yang akan menjadi landasan penelitian
- Berasal dari perspektif para ahli atau hasil penelitian terdahulu yang mendukung topik
- Membangun argumen teoritis atau konseptual yang mendasari penelitian ini
- Menjelaskan karakteristik, dimensi, atau indikator dari variabel yang diteliti
HINDARI: data hasil penelitian spesifik, langkah-langkah metodologi, kesimpulan akhir, atau kalimat generik yang tidak terkait variabel dalam judul.
GUIDE;
        }

        // Bab 3 / Metodologi
        if ($this->ctxContains($ctx, ['bab 3', 'metodologi', 'metode', 'research method', 'desain penelitian', 'instrumen', 'teknik pengumpulan'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK BAB 3 — METODOLOGI
Cari kalimat yang:
- Menjelaskan pendekatan, desain, atau jenis penelitian yang relevan dengan topik "{$researchTitle}"
- Menyebutkan teknik pengumpulan data, instrumen, atau prosedur yang sesuai dengan konteks
- Mendeskripsikan cara menganalisis atau mengolah data dalam studi sejenis
- Membahas validitas, reliabilitas, atau kualitas alat ukur yang relevan
- Memberikan justifikasi metodologis atas pendekatan tertentu
HINDARI: definisi teori murni, latar belakang fenomena, atau pembahasan hasil/temuan.
GUIDE;
        }

        // Bab 4 / Pembahasan / Hasil / Analisis
        if ($this->ctxContains($ctx, ['bab 4', 'pembahasan', 'hasil', 'analisis data', 'result', 'discussion', 'findings', 'temuan'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK BAB 4 — PEMBAHASAN / HASIL
Cari kalimat yang:
- Menyajikan temuan, data, atau hasil yang berkaitan dengan variabel dalam "{$researchTitle}"
- Menginterpretasikan hubungan antar variabel atau menjelaskan makna dari temuan
- Membandingkan hasil dengan teori atau penelitian terdahulu yang relevan
- Menunjukkan pengaruh, korelasi, atau efek dari variabel yang diteliti
- Menyajikan implikasi praktis dari temuan terkait konteks penelitian
HINDARI: kalimat definisi teoritis murni, langkah metodologi, atau latar belakang tanpa kaitannya dengan temuan.
GUIDE;
        }

        // Bab 5 / Kesimpulan / Saran
        if ($this->ctxContains($ctx, ['bab 5', 'kesimpulan', 'simpulan', 'conclusion', 'saran', 'rekomendasi', 'implikasi'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK BAB 5 — KESIMPULAN & SARAN
Cari kalimat yang:
- Merangkum temuan utama atau jawaban atas pertanyaan penelitian terkait "{$researchTitle}"
- Memberikan rekomendasi praktis atau saran kebijakan berdasarkan hasil penelitian
- Menunjukkan kontribusi atau implikasi dari penelitian
- Mengidentifikasi keterbatasan studi atau arah penelitian lanjutan
HINDARI: definisi teori, langkah metodologi detail, atau data mentah tanpa interpretasi.
GUIDE;
        }

        // Landasan Teori (standalone)
        if ($this->ctxContains($ctx, ['landasan teori', 'kerangka teori', 'theoretical framework'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK LANDASAN TEORI
Cari kalimat yang:
- Menjelaskan teori utama yang menjadi fondasi penelitian "{$researchTitle}"
- Mendefinisikan konstruk atau konsep kunci yang digunakan dalam penelitian
- Menggambarkan hubungan antar konsep dalam kerangka teoritis
HINDARI: data empiris, prosedur penelitian, atau hasil temuan.
GUIDE;
        }

        // Analisis Data (standalone)
        if ($this->ctxContains($ctx, ['analisis data', 'data analysis'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK ANALISIS DATA
Cari kalimat yang:
- Menjelaskan teknik atau metode analisis data yang relevan dengan "{$researchTitle}"
- Menyajikan hasil analisis yang mendukung atau menolak hipotesis
- Menginterpretasikan pola, korelasi, atau tren dari data
GUIDE;
        }

        // Kerangka Pemikiran (standalone)
        if ($this->ctxContains($ctx, ['kerangka pemikiran', 'conceptual framework'])) {
            return <<<GUIDE
## PANDUAN SELEKSI UNTUK KERANGKA PEMIKIRAN
Cari kalimat yang:
- Menggambarkan hubungan logis antar variabel dalam penelitian "{$researchTitle}"
- Menjelaskan alur pikir atau model konseptual penelitian
- Mendukung asumsi-asumsi dasar yang mendasari penelitian
GUIDE;
        }

        // Default / custom context
        return <<<GUIDE
## PANDUAN SELEKSI
Cari kalimat yang paling relevan dengan konteks "{$researchContext}" dalam penelitian "{$researchTitle}".
Pilih kutipan yang secara spesifik membahas variabel, konsep, atau aspek yang disebutkan dalam judul penelitian.
HINDARI kutipan generik yang tidak secara langsung berkaitan dengan topik penelitian.
GUIDE;
    }

    /**
     * Helper: check if a lowercase string contains any of the given keywords.
     */
    private function ctxContains(string $lowerCtx, array $keywords): bool
    {
        foreach ($keywords as $kw) {
            if (str_contains($lowerCtx, mb_strtolower($kw))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve position value from research context string.
     */
    private function resolvePositionFromContext(?string $researchContext): string
    {
        if (empty($researchContext)) {
            return 'pembahasan';
        }

        $map = [
            'latar_belakang' => ['Bab 1', 'Pendahuluan', 'Latar Belakang', 'Identifikasi Masalah'],
            'tinjauan_pustaka' => ['Bab 2', 'Tinjauan Pustaka', 'Landasan Teori', 'Kerangka Pemikiran', 'Literature Review'],
            'metodologi' => ['Bab 3', 'Metodologi', 'Metode', 'Research Method', 'Desain Penelitian', 'Instrumen'],
            'pembahasan' => ['Bab 4', 'Pembahasan', 'Hasil', 'Analisis Data', 'Result', 'Discussion', 'Findings'],
            'kesimpulan' => ['Bab 5', 'Kesimpulan', 'Simpulan', 'Conclusion', 'Saran', 'Rekomendasi'],
        ];

        $lower = mb_strtolower($researchContext);
        foreach ($map as $position => $keywords) {
            foreach ($keywords as $kw) {
                if (str_contains($lower, mb_strtolower($kw))) {
                    return $position;
                }
            }
        }

        return 'pembahasan';
    }

    /**
     * Validate that citation quotes actually exist in the source text.
     * Checks against full original content (not just pre-filtered chunk) so valid
     * quotes are not dropped just because they fell outside the filtered window.
     */
    private function validateCitations(array $citations, string $sourceText): array
    {
        if (empty($sourceText)) {
            return $citations; // cannot validate, accept all
        }

        $normalizedSource = preg_replace('/\s+/', ' ', mb_strtolower(trim($sourceText)));

        $valid = [];
        foreach ($citations as $citation) {
            $quote = $citation['quote'] ?? '';
            if (empty(trim($quote))) {
                continue;
            }

            $normalizedQuote = preg_replace('/\s+/', ' ', mb_strtolower(trim($quote)));

            if (mb_strlen($normalizedQuote) < 20) {
                Log::warning('Gemini citation: quote too short, skipped', ['quote_preview' => mb_substr($quote, 0, 80)]);
                continue;
            }

            // Exact substring match
            if (mb_strpos($normalizedSource, $normalizedQuote) !== false) {
                $valid[] = $citation;
                continue;
            }

            // Fuzzy match: check if at least 80% of the quote's words appear in the source
            $quoteWords = array_filter(preg_split('/\s+/', $normalizedQuote));
            $totalWords = count($quoteWords);
            if ($totalWords >= 5) {
                $matchedWords = 0;
                foreach ($quoteWords as $word) {
                    if (mb_strlen($word) > 3 && mb_strpos($normalizedSource, $word) !== false) {
                        $matchedWords++;
                    }
                }
                $matchRatio = $matchedWords / $totalWords;
                if ($matchRatio >= 0.75) {
                    $valid[] = $citation;
                    continue;
                }
            }

            Log::warning('Gemini citation: quote failed validation (not found in source text)', [
                'quote_preview' => mb_substr($quote, 0, 120),
            ]);
        }

        return $valid;
    }

    /**
     * Build structured prompt for Gemini (token-optimized)
     */
    private function buildPrompt(string $content, int $maxWords, string $language, ?string $researchTitle = null): string
    {
        $lang = $language === 'id' ? 'ID' : 'EN';

        $researchHint = '';
        if (! empty($researchTitle)) {
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
                    ->post($this->apiUrl.'?key='.$this->apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
                        ],
                        'generationConfig' => [
                            'temperature' => 0.3,
                            'topK' => 1,
                            'topP' => 1,
                            'maxOutputTokens' => $maxOutputTokens,
                            'stopSequences' => [],
                        ],
                        'safetySettings' => [
                            [
                                'category' => 'HARM_CATEGORY_HARASSMENT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_HATE_SPEECH',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_SEXUALLY_EXPLICIT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                            [
                                'category' => 'HARM_CATEGORY_DANGEROUS_CONTENT',
                                'threshold' => 'BLOCK_NONE',
                            ],
                        ],
                    ]);

                if ($response->successful()) {
                    return $response->json();
                }

                throw new Exception('API request failed: '.$response->body());
            } catch (Exception $e) {
                $retries++;
                Log::warning("Gemini API request failed (attempt {$retries}): ".$e->getMessage());

                if ($retries >= $this->maxRetries) {
                    throw new Exception('Gemini API request failed after '.$this->maxRetries.' attempts: '.$e->getMessage());
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
            if (! isset($response['candidates'][0]['content']['parts'][0]['text'])) {
                $finishReason = $response['candidates'][0]['finishReason'] ?? 'UNKNOWN';
                throw new Exception('Invalid response structure from Gemini API (finishReason: '.$finishReason.')');
            }

            $text = $response['candidates'][0]['content']['parts'][0]['text'];

            $jsonData = $this->extractJsonFromText($text);

            if ($jsonData !== null) {
                $summary = $jsonData['summary'] ?? $this->extractSummaryFallback($text);
                $keyPoints = $jsonData['key_points'] ?? [];

                $wordCount = str_word_count($summary);
                if ($wordCount > 300) {
                    $summary = implode(' ', array_slice(str_word_count($summary, 1), 0, 300)).'...';
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
            Log::error('Failed to parse Gemini response: '.$e->getMessage());
            throw new Exception('Failed to parse Gemini API response: '.$e->getMessage());
        }
    }

    private function extractJsonFromText(string $text): ?array
    {
        $cleanText = preg_replace('/```json\s*/i', '', $text);
        $cleanText = preg_replace('/```\s*/', '', $cleanText);
        $cleanText = trim($cleanText);

        // Try recursive balanced braces first
        if (preg_match('/\{([^{}]|(?R))*\}/s', $cleanText, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Greedy fallback: trim to first { and last }
        $firstBrace = strpos($cleanText, '{');
        $lastBrace = strrpos($cleanText, '}');
        if ($firstBrace !== false && $lastBrace !== false && $lastBrace > $firstBrace) {
            $candidate = substr($cleanText, $firstBrace, $lastBrace - $firstBrace + 1);
            $decoded = json_decode($candidate, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        // Walk backwards through closing braces
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
                $summary .= $line.' ';
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
        return ! empty($this->apiKey);
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
                    ->post($this->apiUrl.'?key='.$this->apiKey, [
                        'contents' => [
                            [
                                'parts' => [
                                    ['text' => $prompt],
                                ],
                            ],
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
                        ],
                    ]);

                if ($response->successful()) {
                    $data = $response->json();

                    if (! isset($data['candidates'][0]['content']['parts'][0]['text'])) {
                        throw new Exception('Invalid response structure');
                    }

                    return [
                        'success' => true,
                        'content' => $data['candidates'][0]['content']['parts'][0]['text'],
                        'tokens_used' => $data['usageMetadata']['totalTokenCount'] ?? 0,
                    ];
                }

                throw new Exception('API request failed: '.$response->body());
            } catch (Exception $e) {
                $retries++;
                Log::warning("Gemini generateWithPrompt failed (attempt {$retries}): ".$e->getMessage());

                if ($retries >= $this->maxRetries) {
                    Log::error('Gemini generateWithPrompt failed after '.$this->maxRetries.' attempts');

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

<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

/**
 * Verify that AI-generated citations actually exist verbatim in the original text.
 * Uses exact substring matching first, then fallback to fuzzy similarity search.
 */
class CitationVerifierService
{
    /**
     * Maximum characters around a quote to include as context.
     */
    private int $contextRadius = 200;

    /**
     * Minimum similarity percentage for fuzzy fallback match.
     */
    private float $minSimilarity = 75.0;

    /**
     * Verify citations against original text.
     *
     * @param  array  $citations  Array of ['quote'=>string, 'relevance'=>string, 'position'=>string]
     * @param  string  $originalText  Full original text from article/PDF
     * @return array Verified citations with exact quotes from original text
     */
    public function verify(array $citations, string $originalText): array
    {
        if (empty($citations) || empty($originalText)) {
            return [];
        }

        $verified = [];
        $originalText = $this->normalizeText($originalText);

        foreach ($citations as $citation) {
            $quote = $citation['quote'] ?? '';
            if (empty($quote)) {
                continue;
            }

            $result = $this->findExactMatch($quote, $originalText);

            if ($result['found']) {
                $verified[] = [
                    'quote' => $result['exact_quote'],
                    'paraphrase' => $citation['paraphrase'] ?? null,
                    'relevance' => $citation['relevance'] ?? '',
                    'position' => $citation['position'] ?? '',
                    'context' => $result['context'],
                    'confidence' => 'exact',
                ];
            } else {
                // Fallback: try fuzzy match
                $fuzzy = $this->findFuzzyMatch($quote, $originalText);
                if ($fuzzy['found']) {
                    $verified[] = [
                        'quote' => $fuzzy['exact_quote'],
                        'paraphrase' => $citation['paraphrase'] ?? null,
                        'relevance' => $citation['relevance'] ?? '',
                        'position' => $citation['position'] ?? '',
                        'context' => $fuzzy['context'],
                        'confidence' => 'fuzzy',
                    ];
                } else {
                    Log::warning('Citation not found in original text', [
                        'quote_preview' => mb_substr($quote, 0, 100),
                        'best_similarity' => $fuzzy['similarity'] ?? 0,
                    ]);
                }
            }
        }

        return $verified;
    }

    /**
     * Try to find exact substring match.
     */
    private function findExactMatch(string $quote, string $originalText): array
    {
        $normalizedQuote = $this->normalizeText($quote);

        // Direct exact match
        $pos = mb_strpos($originalText, $normalizedQuote);
        if ($pos !== false) {
            return [
                'found' => true,
                'exact_quote' => mb_substr($originalText, $pos, mb_strlen($normalizedQuote)),
                'context' => $this->extractContext($originalText, $pos, mb_strlen($normalizedQuote)),
            ];
        }

        // Try with relaxed whitespace
        $relaxedQuote = preg_replace('/\s+/', ' ', trim($normalizedQuote));
        $relaxedOriginal = preg_replace('/\s+/', ' ', $originalText);
        $pos = mb_strpos($relaxedOriginal, $relaxedQuote);
        if ($pos !== false) {
            return [
                'found' => true,
                'exact_quote' => $this->reconstructExactFromRelaxed($originalText, $relaxedOriginal, $pos, mb_strlen($relaxedQuote)),
                'context' => $this->extractContext($originalText, $this->findBestPosition($originalText, $relaxedQuote), mb_strlen($relaxedQuote)),
            ];
        }

        // Try stripping common punctuation variations
        $cleanQuote = $this->stripPunctuation($normalizedQuote);
        if (mb_strlen($cleanQuote) > 20) {
            $cleanOriginal = $this->stripPunctuation($originalText);
            $pos = mb_strpos($cleanOriginal, $cleanQuote);
            if ($pos !== false) {
                // Map back to original
                $origPos = $this->mapCleanToOriginal($originalText, $cleanOriginal, $pos, mb_strlen($cleanQuote));
                if ($origPos !== null) {
                    return [
                        'found' => true,
                        'exact_quote' => mb_substr($originalText, $origPos, $this->estimateOriginalLength($originalText, $cleanOriginal, $pos, mb_strlen($cleanQuote))),
                        'context' => $this->extractContext($originalText, $origPos, mb_strlen($cleanQuote)),
                    ];
                }
            }
        }

        return ['found' => false, 'exact_quote' => '', 'context' => ''];
    }

    /**
     * Fuzzy match using similar_text on sentences.
     */
    private function findFuzzyMatch(string $quote, string $originalText): array
    {
        // Split original into sentences
        $sentences = $this->splitSentences($originalText);
        if (empty($sentences)) {
            return ['found' => false, 'similarity' => 0];
        }

        $bestSentence = '';
        $bestSimilarity = 0.0;
        $bestIndex = -1;

        $normalizedQuote = $this->normalizeText($quote);

        foreach ($sentences as $i => $sentence) {
            $normalizedSentence = $this->normalizeText($sentence);
            similar_text($normalizedQuote, $normalizedSentence, $percent);
            if ($percent > $bestSimilarity) {
                $bestSimilarity = $percent;
                $bestSentence = $sentence;
                $bestIndex = $i;
            }
        }

        if ($bestSimilarity >= $this->minSimilarity && $bestIndex >= 0) {
            $pos = mb_strpos($originalText, $bestSentence);

            return [
                'found' => true,
                'exact_quote' => $bestSentence,
                'context' => $this->extractContext($originalText, $pos !== false ? $pos : 0, mb_strlen($bestSentence)),
                'similarity' => round($bestSimilarity, 1),
            ];
        }

        return ['found' => false, 'similarity' => round($bestSimilarity, 1)];
    }

    /**
     * Extract context around a match.
     */
    private function extractContext(string $text, int $pos, int $len): string
    {
        $start = max(0, $pos - $this->contextRadius);
        $end = min(mb_strlen($text), $pos + $len + $this->contextRadius);

        return mb_substr($text, $start, $end - $start);
    }

    /**
     * Normalize text for comparison.
     */
    private function normalizeText(string $text): string
    {
        // Trim and reduce whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));

        return $text;
    }

    /**
     * Strip punctuation for lenient matching.
     */
    private function stripPunctuation(string $text): string
    {
        return preg_replace('/[^\p{L}\p{N}\s]/u', '', mb_strtolower($text));
    }

    /**
     * Split text into sentences.
     */
    private function splitSentences(string $text): array
    {
        // Split by sentence-ending punctuation
        $sentences = preg_split('/(?<=[.!?。！？])\s+/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        $result = [];
        foreach ($sentences as $s) {
            $s = trim($s);
            if (mb_strlen($s) > 20) {
                $result[] = $s;
            }
        }

        return $result;
    }

    /**
     * Reconstruct exact text from relaxed match position.
     */
    private function reconstructExactFromRelaxed(string $original, string $relaxed, int $relaxedPos, int $relaxedLen): string
    {
        // Find corresponding position in original
        $origPos = $this->findBestPosition($original, mb_substr($relaxed, $relaxedPos, $relaxedLen));
        if ($origPos !== null) {
            // Estimate end position
            $origEnd = $origPos + $relaxedLen;
            // Expand to include whitespace
            while ($origEnd < mb_strlen($original) && preg_match('/\s/', mb_substr($original, $origEnd, 1))) {
                $origEnd++;
            }

            return mb_substr($original, $origPos, min($origEnd - $origPos + 20, mb_strlen($original) - $origPos));
        }

        return mb_substr($original, 0, min($relaxedLen + 50, mb_strlen($original)));
    }

    /**
     * Find best position in original text for a substring.
     */
    private function findBestPosition(string $original, string $substring): ?int
    {
        $pos = mb_strpos($original, $substring);
        if ($pos !== false) {
            return $pos;
        }
        // Try first 10 words of substring
        $words = explode(' ', $substring);
        $head = implode(' ', array_slice($words, 0, min(10, count($words))));
        $pos = mb_strpos($original, $head);
        if ($pos !== false) {
            return $pos;
        }

        return null;
    }

    /**
     * Map position from clean text back to original.
     */
    private function mapCleanToOriginal(string $original, string $clean, int $cleanPos, int $cleanLen): ?int
    {
        // Find the character in original that corresponds to cleanPos
        $origIndex = 0;
        $cleanIndex = 0;
        $origLen = mb_strlen($original);

        while ($origIndex < $origLen && $cleanIndex < $cleanPos) {
            $origChar = mb_substr($original, $origIndex, 1);
            $cleanChar = mb_substr($clean, $cleanIndex, 1);
            if ($this->charsMatch($origChar, $cleanChar)) {
                $cleanIndex++;
            }
            $origIndex++;
        }

        if ($cleanIndex >= $cleanPos - 2) {
            return $origIndex;
        }

        return null;
    }

    private function charsMatch(string $a, string $b): bool
    {
        return mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $a)) === mb_strtolower(preg_replace('/[^\p{L}\p{N}]/u', '', $b));
    }

    private function estimateOriginalLength(string $original, string $clean, int $cleanPos, int $cleanLen): int
    {
        // Rough estimate: original is about 1.1x longer than clean due to punctuation
        $start = $this->mapCleanToOriginal($original, $clean, $cleanPos, $cleanLen);
        $end = $this->mapCleanToOriginal($original, $clean, $cleanPos + $cleanLen, $cleanLen);
        if ($start !== null && $end !== null && $end > $start) {
            return $end - $start;
        }

        return (int) ($cleanLen * 1.15) + 10;
    }
}

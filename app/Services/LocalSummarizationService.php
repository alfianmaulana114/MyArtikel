<?php

namespace App\Services;

use Exception;
use Illuminate\Support\Facades\Cache;

class LocalSummarizationService
{
    private int $maxSentences = 5;

    private int $maxKeyPoints = 5;

    /**
     * Generate extractive summary using local algorithms
     */
    public function generateSummary(string $content, int $maxWords = 150, string $language = 'id'): array
    {
        $cacheKey = $this->generateCacheKey($content, $maxWords, $language);

        // Check cache first
        if ($cachedSummary = Cache::get($cacheKey)) {
            return $cachedSummary;
        }

        try {
            // Preprocess content
            $cleanedContent = $this->preprocessContent($content);
            $sentences = $this->extractSentences($cleanedContent);

            if (count($sentences) < 3) {
                // If too few sentences, return the whole content as summary
                $summary = [
                    'summary' => $this->truncateText($cleanedContent, $maxWords),
                    'key_points' => [$cleanedContent],
                    'method' => 'extractive',
                    'sentences_used' => count($sentences),
                    'confidence' => 0.5,
                ];
            } else {
                // Generate proper extractive summary
                $summary = $this->generateExtractiveSummary($sentences, $maxWords, $language);
            }

            // Cache the result for 24 hours
            Cache::put($cacheKey, $summary, now()->addHours(24));

            return $summary;

        } catch (Exception $e) {
            // Fallback to simple truncation
            return [
                'summary' => $this->truncateText($content, $maxWords),
                'key_points' => [substr($content, 0, 200).'...'],
                'method' => 'fallback',
                'error' => $e->getMessage(),
                'confidence' => 0.3,
            ];
        }
    }

    /**
     * Preprocess content for better summarization
     */
    private function preprocessContent(string $content): string
    {
        // Remove extra whitespace
        $content = preg_replace('/\s+/', ' ', $content);

        // Remove special characters but keep punctuation
        $content = preg_replace('/[^\w\s\.\,\!\?\-]/', '', $content);

        // Remove very short sentences (less than 10 characters)
        $sentences = $this->extractSentences($content);
        $filteredSentences = array_filter($sentences, function ($sentence) {
            return strlen(trim($sentence)) > 10;
        });

        return implode(' ', $filteredSentences);
    }

    /**
     * Extract sentences from content
     */
    private function extractSentences(string $content): array
    {
        // Split by sentence endings
        $sentences = preg_split('/[.!?]+/', $content);

        // Clean and filter sentences
        $cleanedSentences = [];
        foreach ($sentences as $sentence) {
            $sentence = trim($sentence);
            if (strlen($sentence) > 10) { // Minimum sentence length
                $cleanedSentences[] = $sentence;
            }
        }

        return $cleanedSentences;
    }

    /**
     * Generate extractive summary using sentence ranking
     */
    private function generateExtractiveSummary(array $sentences, int $maxWords, string $language): array
    {
        // Calculate sentence scores based on multiple factors
        $scoredSentences = [];

        foreach ($sentences as $index => $sentence) {
            $score = $this->calculateSentenceScore($sentence, $sentences, $index);
            $scoredSentences[] = [
                'sentence' => $sentence,
                'score' => $score,
                'length' => str_word_count($sentence),
            ];
        }

        // Sort by score descending
        usort($scoredSentences, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Select top sentences
        $selectedSentences = [];
        $totalWords = 0;
        $maxSentences = min($this->maxSentences, count($scoredSentences));

        for ($i = 0; $i < $maxSentences; $i++) {
            $sentenceData = $scoredSentences[$i];
            $sentenceWords = str_word_count($sentenceData['sentence']);

            if ($totalWords + $sentenceWords <= $maxWords) {
                $selectedSentences[] = $sentenceData;
                $totalWords += $sentenceWords;
            } else {
                break;
            }
        }

        // Sort selected sentences by original position
        usort($selectedSentences, function ($a, $b) use ($sentences) {
            $posA = array_search($a['sentence'], $sentences);
            $posB = array_search($b['sentence'], $sentences);

            return $posA <=> $posB;
        });

        // Build summary
        $summaryText = implode('. ', array_column($selectedSentences, 'sentence'));
        $summaryText = $this->truncateText($summaryText, $maxWords);

        // Extract key points
        $keyPoints = $this->extractKeyPoints($sentences, $language);

        return [
            'summary' => $summaryText,
            'key_points' => $keyPoints,
            'method' => 'extractive',
            'sentences_used' => count($selectedSentences),
            'confidence' => $this->calculateOverallConfidence($selectedSentences),
        ];
    }

    /**
     * Calculate sentence importance score
     */
    private function calculateSentenceScore(string $sentence, array $allSentences, int $position): float
    {
        $score = 0;
        $words = str_word_count(strtolower($sentence), 1);

        // Position bonus (earlier sentences often more important)
        $positionBonus = max(0, 1 - ($position / count($allSentences)));
        $score += $positionBonus * 0.2;

        // Length score (prefer medium-length sentences)
        $wordCount = count($words);
        if ($wordCount >= 8 && $wordCount <= 25) {
            $score += 0.3;
        }

        // Keyword frequency score
        $keywordScore = $this->calculateKeywordScore($sentence, $allSentences);
        $score += $keywordScore * 0.4;

        // Presence of important words (numbers, proper nouns, etc.)
        if (preg_match('/\d+/', $sentence)) {
            $score += 0.1; // Contains numbers
        }

        if (preg_match('/[A-Z][a-z]+/', $sentence)) {
            $score += 0.1; // Contains proper nouns
        }

        return min(1.0, $score);
    }

    /**
     * Calculate keyword importance score
     */
    private function calculateKeywordScore(string $sentence, array $allSentences): float
    {
        $sentenceWords = array_count_values(str_word_count(strtolower($sentence), 1));
        $allWords = [];

        foreach ($allSentences as $s) {
            $words = str_word_count(strtolower($s), 1);
            foreach ($words as $word) {
                if (strlen($word) > 3) { // Skip very short words
                    $allWords[] = $word;
                }
            }
        }

        $allWordFreq = array_count_values($allWords);
        $score = 0;

        foreach ($sentenceWords as $word => $freq) {
            if (strlen($word) > 3 && isset($allWordFreq[$word])) {
                // TF-IDF-like scoring
                $tf = $freq / count($sentenceWords);
                $idf = log(count($allSentences) / $allWordFreq[$word]);
                $score += $tf * $idf;
            }
        }

        return min(1.0, $score);
    }

    /**
     * Extract key points from sentences
     */
    private function extractKeyPoints(array $sentences, string $language): array
    {
        $keyPoints = [];

        // Score sentences for key point extraction
        $scoredSentences = [];
        foreach ($sentences as $index => $sentence) {
            $score = $this->calculateSentenceScore($sentence, $sentences, $index);
            $scoredSentences[] = [
                'sentence' => $sentence,
                'score' => $score,
            ];
        }

        // Sort by score
        usort($scoredSentences, function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        // Select top sentences as key points
        $maxPoints = min($this->maxKeyPoints, count($scoredSentences));
        for ($i = 0; $i < $maxPoints; $i++) {
            $sentence = trim($scoredSentences[$i]['sentence']);
            if (strlen($sentence) > 20) { // Minimum length for key point
                $keyPoints[] = $this->formatKeyPoint($sentence, $language);
            }
        }

        return $keyPoints;
    }

    /**
     * Format key point text
     */
    private function formatKeyPoint(string $sentence, string $language): string
    {
        // Remove trailing punctuation
        $sentence = rtrim($sentence, '.!?');

        // Capitalize first letter
        $sentence = ucfirst(strtolower($sentence));

        // Limit length
        if (strlen($sentence) > 150) {
            $sentence = substr($sentence, 0, 150).'...';
        }

        return $sentence;
    }

    /**
     * Calculate overall confidence score
     */
    private function calculateOverallConfidence(array $selectedSentences): float
    {
        if (empty($selectedSentences)) {
            return 0.0;
        }

        $avgScore = array_sum(array_column($selectedSentences, 'score')) / count($selectedSentences);
        $sentenceCountBonus = min(0.3, count($selectedSentences) * 0.1);

        return min(1.0, $avgScore + $sentenceCountBonus);
    }

    /**
     * Truncate text to word limit
     */
    private function truncateText(string $text, int $maxWords): string
    {
        $words = str_word_count($text, 1);

        if (count($words) <= $maxWords) {
            return $text;
        }

        $truncated = implode(' ', array_slice($words, 0, $maxWords));

        return $truncated.'...';
    }

    /**
     * Generate cache key
     */
    private function generateCacheKey(string $content, int $maxWords, string $language): string
    {
        $contentHash = md5($content);

        return "local_summary:{$contentHash}:{$maxWords}:{$language}";
    }

    /**
     * Check if service is available (always true for local)
     */
    public function isAvailable(): bool
    {
        return true;
    }
}

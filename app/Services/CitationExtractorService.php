<?php

namespace App\Services;

/**
 * Extract the most relevant chunks from article text relative to a research title.
 * Uses a lightweight local algorithm (sentence scoring by keyword overlap)
 * to pre-filter content before sending to AI. This saves tokens and improves
 * citation quality because AI only sees the most relevant passages.
 */
class CitationExtractorService
{
    /**
     * Extract top-N most relevant chunks (paragraphs) from text.
     *
     * @param  string  $text  Full article text
     * @param  string  $researchTitle  User's research title
     * @param  int  $maxChars  Total max characters to return (default ~6000 chars ≈ 1500 tokens)
     * @param  int  $minChunkLen  Minimum chunk length in characters
     * @return string Concatenated relevant chunks
     */
    public function extractRelevantChunks(string $text, string $researchTitle, int $maxChars = 6000, int $minChunkLen = 80): string
    {
        if (empty($text) || empty($researchTitle)) {
            return $text;
        }

        // Split into paragraphs/chunks
        $chunks = $this->splitIntoChunks($text, $minChunkLen);
        if (count($chunks) <= 3) {
            return mb_substr($text, 0, $maxChars);
        }

        // Build keyword set from research title
        $keywords = $this->extractKeywords($researchTitle);
        if (empty($keywords)) {
            return mb_substr($text, 0, $maxChars);
        }

        // Score each chunk
        $scored = [];
        foreach ($chunks as $index => $chunk) {
            $score = $this->scoreChunk($chunk, $keywords);
            $scored[] = [
                'index' => $index,
                'chunk' => $chunk,
                'score' => $score,
            ];
        }

        // Sort by score descending
        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        // Take top chunks until maxChars reached
        // Prioritize: highest score first, then original order for coherence
        $selected = [];
        $totalLen = 0;
        $takeCount = min(10, count($scored)); // max 10 chunks

        for ($i = 0; $i < $takeCount; $i++) {
            $chunk = $scored[$i]['chunk'];
            $len = mb_strlen($chunk);
            if ($totalLen + $len > $maxChars && $totalLen > 0) {
                break;
            }
            $selected[$scored[$i]['index']] = $chunk;
            $totalLen += $len;
        }

        // Re-sort by original index to maintain text flow
        ksort($selected);

        if (empty($selected)) {
            return mb_substr($text, 0, $maxChars);
        }

        return implode("\n\n", $selected);
    }

    /**
     * Split text into paragraphs/sentences chunks.
     */
    private function splitIntoChunks(string $text, int $minLen): array
    {
        // Normalize whitespace
        $text = preg_replace('/\s+/', ' ', $text);

        // Split by paragraph boundaries (double newline or period+space)
        // First try paragraph split
        $paragraphs = preg_split('/\n\s*\n|\r\n\s*\r\n/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $chunks = [];
        foreach ($paragraphs as $p) {
            $p = trim($p);
            if (mb_strlen($p) < $minLen) {
                // Merge short paragraph with previous if exists
                if (! empty($chunks)) {
                    $chunks[count($chunks) - 1] .= ' '.$p;
                } else {
                    $chunks[] = $p;
                }
            } else {
                $chunks[] = $p;
            }
        }

        // If still too many short chunks, merge adjacent
        $merged = [];
        $buffer = '';
        foreach ($chunks as $chunk) {
            $buffer .= ($buffer ? ' ' : '').$chunk;
            if (mb_strlen($buffer) >= $minLen * 2) {
                $merged[] = $buffer;
                $buffer = '';
            }
        }
        if ($buffer !== '') {
            if (! empty($merged)) {
                $merged[count($merged) - 1] .= ' '.$buffer;
            } else {
                $merged[] = $buffer;
            }
        }

        return $merged ?: [$text];
    }

    /**
     * Extract meaningful keywords and phrases from a string.
     */
    private function extractKeywords(string $text): array
    {
        $text = mb_strtolower($text);
        // Remove punctuation
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $stopWords = [
            'yang', 'untuk', 'dengan', 'tidak', 'dari', 'dalam', 'adalah', 'akan', 'oleh',
            'ini', 'itu', 'dan', 'atau', 'jika', 'pada', 'dapat', 'sudah', 'saya', 'kami',
            'nya', 'lebih', 'juga', 'telah', 'bahwa', 'hanya', 'sebagai', 'mereka', 'kita',
            'anda', 'bisa', 'ada', 'tetapi', 'namun', 'saat', 'karena', 'seperti', 'agar',
            'sangat', 'serta', 'antara', 'sebuah', 'suatu', 'beberapa', 'selain', 'tersebut',
            'the', 'and', 'of', 'to', 'a', 'in', 'is', 'that', 'for', 'with', 'as', 'on',
            'this', 'by', 'from', 'are', 'was', 'or', 'an', 'be', 'it', 'at', 'have',
        ];

        $keywords = [];
        $cleanWords = [];
        foreach ($words as $word) {
            if (mb_strlen($word) >= 3 && ! in_array($word, $stopWords)) {
                $cleanWords[] = $word;
                $keywords[] = $word;
            }
        }

        // Extract bigrams and trigrams for phrase matching
        for ($i = 0; $i < count($cleanWords) - 1; $i++) {
            $bigram = $cleanWords[$i].' '.$cleanWords[$i + 1];
            $keywords[] = $bigram;
            if ($i < count($cleanWords) - 2) {
                $trigram = $cleanWords[$i].' '.$cleanWords[$i + 1].' '.$cleanWords[$i + 2];
                $keywords[] = $trigram;
            }
        }

        // Deduplicate and get frequency
        $freq = array_count_values($keywords);
        arsort($freq);

        // Return top unique keywords/phrases, prioritizing longer phrases
        $sorted = array_keys($freq);
        usort($sorted, function ($a, $b) use ($freq) {
            $lenDiff = mb_strlen($b) <=> mb_strlen($a);
            if ($lenDiff !== 0) {
                return $lenDiff;
            }

            return $freq[$b] <=> $freq[$a];
        });

        return array_slice($sorted, 0, 30);
    }

    /**
     * Score a chunk based on keyword and phrase overlap with research title/context.
     */
    private function scoreChunk(string $chunk, array $keywords): float
    {
        $chunkLower = mb_strtolower($chunk);
        $score = 0.0;

        foreach ($keywords as $keyword) {
            $count = mb_substr_count($chunkLower, $keyword);
            // Phrases (multi-word) are much more significant than single words
            $wordCount = substr_count($keyword, ' ') + 1;
            $weight = 1.0 + (mb_strlen($keyword) * 0.05) + ($wordCount * 1.5);
            $score += $count * $weight;
        }

        // Normalize by chunk length to avoid bias toward long chunks
        $chunkLen = mb_strlen($chunk);
        if ($chunkLen > 0) {
            $score = ($score / sqrt($chunkLen)) * 100;
        }

        // Bonus for chunks that contain numbers (often indicate data/results sections)
        if (preg_match('/\d/', $chunk)) {
            $score *= 1.1;
        }

        // Bonus for chunks with quotation marks (already quoted in text)
        if (preg_match('/["\'\"]/', $chunk)) {
            $score *= 1.05;
        }

        // Bonus for academic indicator words
        if (preg_match('/\b(hasil|menunjukkan|penelitian|metode|analisis|data|kesimpulan|teori|hipotesis)\b/u', $chunkLower)) {
            $score *= 1.15;
        }

        return $score;
    }
}

<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Project;
use App\Models\ProjectChatMessage;
use App\Models\Summary;
use Illuminate\Support\Facades\Log;

class ProjectChatService
{
    private GeminiSummarizationService $geminiService;

    public function __construct(GeminiSummarizationService $geminiService)
    {
        $this->geminiService = $geminiService;
    }

    /**
     * Retrieve relevant articles from project using simple PHP LIKE search.
     * No AI/vector DB used — fully token-free retrieval.
     */
    public function retrieveRelevantArticles(Project $project, string $query): array
    {
        $articleIds = $project->articles()
            ->pluck('articles.id')
            ->toArray();

        if (empty($articleIds)) {
            return [];
        }

        $keywords = $this->extractKeywords($query);

        // Fetch all summaries for project's articles
        $allSummaries = Summary::whereIn('article_id', $articleIds)
            ->where('status', 'completed')
            ->with('article:id,title,excerpt')
            ->get()
            ->keyBy('article_id');

        // If no summaries exist yet, return articles with excerpts only
        if ($allSummaries->isEmpty()) {
            $articles = Article::whereIn('id', $articleIds)
                ->select('id', 'title', 'excerpt')
                ->get();

            return $articles->map(fn ($a) => [
                'article_id' => $a->id,
                'title' => $a->title,
                'summary' => '',
                'excerpt' => $a->excerpt ?? '',
                'key_points' => [],
            ])->toArray();
        }

        // Score by keyword match
        $scored = [];
        foreach ($allSummaries as $articleId => $summary) {
            $score = 0;
            $context = [
                'article_id' => $articleId,
                'title' => $summary->article->title ?? '',
                'summary' => $summary->content,
                'excerpt' => $summary->article->excerpt ?? '',
                'key_points' => $summary->key_points ?? [],
            ];

            if (! empty($keywords)) {
                $score += $this->countKeywordMatches($summary->article->title ?? '', $keywords) * 3;
                $score += $this->countKeywordMatches($summary->content, $keywords) * 2;
                foreach ($summary->key_points ?? [] as $point) {
                    $score += $this->countKeywordMatches($point, $keywords);
                }
            }

            $scored[$articleId] = ['score' => $score, 'context' => $context];
        }

        // Sort by score desc
        uasort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

        // If no keyword matches at all, return ALL articles (fallback)
        $hasMatches = ! empty($keywords) && reset($scored)['score'] > 0;
        if ($hasMatches) {
            $top = array_slice($scored, 0, 3, true);
        } else {
            $top = $scored; // return all
        }

        return array_column($top, 'context');
    }

    /**
     * Generate assistant response using Gemini with project context.
     */
    public function generateResponse(Project $project, string $userQuery, array $contextArticles, array $history = []): array
    {
        if (empty($contextArticles)) {
            return [
                'content' => 'Project ini belum memiliki artikel. Tambahkan artikel terlebih dahulu supaya aku bisa membantu.',
                'context_articles' => [],
            ];
        }

        $result = $this->geminiService->generateWithPrompt(
            $this->buildSystemPrompt($contextArticles, $userQuery),
            ['max_tokens' => 1024, 'temperature' => 0.5]
        );

        if (! $result['success']) {
            Log::error('Project chat generation failed: '.($result['error'] ?? 'unknown'));

            return [
                'content' => 'Maaf, terjadi kesalahan saat memproses pertanyaan. Silakan coba lagi.',
                'context_articles' => array_column($contextArticles, 'article_id'),
            ];
        }

        return [
            'content' => $result['content'],
            'context_articles' => array_column($contextArticles, 'article_id'),
        ];
    }

    /**
     * Save a message to the database.
     */
    public function saveMessage(int $projectId, int $userId, string $role, string $content, array $contextArticles = []): ProjectChatMessage
    {
        return ProjectChatMessage::create([
            'project_id' => $projectId,
            'user_id' => $userId,
            'role' => $role,
            'content' => $content,
            'context_articles' => $contextArticles,
        ]);
    }

    /**
     * Get chat history for a project.
     */
    public function getHistory(int $projectId, int $userId, int $limit = 50): array
    {
        return ProjectChatMessage::where('project_id', $projectId)
            ->where('user_id', $userId)
            ->orderBy('created_at', 'asc')
            ->limit($limit)
            ->get()
            ->map(fn ($msg) => [
                'role' => $msg->role,
                'content' => $msg->content,
                'context_articles' => $msg->context_articles ?? [],
                'created_at' => $msg->created_at->toISOString(),
            ])
            ->toArray();
    }

    private function extractKeywords(string $query): array
    {
        // Simple keyword extraction: split by space, filter short words and stop words
        $stopWords = ['yang', 'dan', 'atau', 'dari', 'di', 'ke', 'pada', 'untuk', 'dengan', 'ini', 'itu', 'adalah', 'sebuah', 'the', 'and', 'or', 'of', 'in', 'to', 'for', 'with', 'is', 'a', 'an'];
        $words = preg_split('/\s+/', strtolower(trim($query)));
        $keywords = [];
        foreach ($words as $word) {
            $clean = preg_replace('/[^a-z0-9]/', '', $word);
            if (strlen($clean) >= 3 && ! in_array($clean, $stopWords)) {
                $keywords[] = $clean;
            }
        }

        return array_unique($keywords);
    }

    private function countKeywordMatches(string $text, array $keywords): int
    {
        $lower = strtolower($text);
        $count = 0;
        foreach ($keywords as $keyword) {
            $count += substr_count($lower, $keyword);
        }

        return $count;
    }

    private function buildSystemPrompt(array $contextArticles, string $userQuery): string
    {
        $contextText = '';
        foreach ($contextArticles as $i => $article) {
            $contextText .= 'Artikel '.($i + 1).": {$article['title']}\n";
            if (! empty($article['summary'])) {
                $contextText .= "Ringkasan: {$article['summary']}\n";
            }
            if (! empty($article['key_points'])) {
                $contextText .= 'Poin Kunci: '.implode(', ', $article['key_points'])."\n";
            }
            if (! empty($article['excerpt'])) {
                $contextText .= "Kutipan: {$article['excerpt']}\n";
            }
            $contextText .= "\n";
        }

        return "Kamu adalah asisten riset pribadi bernama 'Asisten Riset'. Tugasmu adalah membantu mahasiswa S1 memahami artikel-artikel di project mereka.\n\nPENTING:\n1. Selalu jawab berdasarkan data artikel yang disediakan di bawah.\n2. Jika pertanyaan tidak 100% tercakup, tetap berikan jawaban terbaik berdasarkan apa yang ada di data. Hubungkan konsep yang relevan.\n3. JANGAN pernah bilang 'tidak menemukan artikel relevan' atau 'tidak bisa menjawab'. Selalu berikan sesuatu yang bermanfaat dari data yang ada.\n4. Jika data terbatas, akui keterbatasannya ringkas lalu tetap berikan insight dari data yang ada.\n5. Gunakan bahasa Indonesia yang natural dan mudah dipahami mahasiswa.\n6. Format jawaban dengan jelas: gunakan paragraf, bullet point, atau penomoran bila perlu.\n\nData Artikel:\n{$contextText}\nPertanyaan: {$userQuery}\n\nJawaban:";
    }
}

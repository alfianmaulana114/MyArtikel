<?php

namespace App\Services;

use App\Models\Article;
use App\Models\Note;
use App\Models\SearchAnalytics;
use App\Models\SearchHistory;
use App\Models\SearchSuggestion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchService
{
    protected int $cacheTimeout = 300; // 5 minutes
    protected int $maxSuggestions = 10;
    protected array $stopWords = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'
    ];
    
    /**
     * Perform advanced search across articles and notes
     */
    public function search(string $query, array $filters = [], int $perPage = 20, int $page = 1): array
    {
        try {
            $userId = Auth::id();
            $cacheKey = $this->generateCacheKey($query, $filters, $userId, $perPage, $page);
            
            // Check cache first
            $cachedResults = Cache::get($cacheKey);
            if ($cachedResults) {
                return $cachedResults;
            }
            
            // Process query
            $processedQuery = $this->processQuery($query);
            $keywords = $this->extractKeywords($processedQuery);
            
            // Build search results
            $results = $this->buildSearchResults($processedQuery, $keywords, $filters, $perPage, $page);
            
            // Record search history
            $this->recordSearchHistory($query, $filters, $results['total_count']);
            
            // Update search analytics
            $this->updateSearchAnalytics($query, $keywords);
            
            // Cache results
            Cache::put($cacheKey, $results, $this->cacheTimeout);
            
            return $results;
            
        } catch (\Exception $e) {
            Log::error('Search error', [
                'query' => $query,
                'filters' => $filters,
                'error' => $e->getMessage()
            ]);
            
            return $this->getEmptyResults();
        }
    }
    
    /**
     * Get search suggestions based on query
     */
    public function getSuggestions(string $query, int $limit = 10): Collection
    {
        if (strlen($query) < 2) {
            return collect();
        }
        
        $cacheKey = "search_suggestions_{$query}_{$limit}";
        
        return Cache::remember($cacheKey, $this->cacheTimeout, function () use ($query, $limit) {
            $suggestions = collect();
            
            // Get query-based suggestions
            $querySuggestions = SearchSuggestion::where('suggestion', 'like', "%{$query}%")
                ->where('type', 'query')
                ->orderByDesc('popularity')
                ->limit($limit)
                ->get();
            
            $suggestions = $suggestions->merge($querySuggestions);
            
            // Get tag-based suggestions
            $userId = Auth::id();
            $tagSuggestions = \App\Models\Tag::where(function ($q) use ($userId) {
                    $q->where('user_id', $userId)->orWhereNull('user_id');
                })
                ->where('name', 'like', "%{$query}%")
                ->orderByDesc('usage_count')
                ->limit($limit - $suggestions->count())
                ->get()
                ->map(function ($tag) {
                    return new SearchSuggestion([
                        'suggestion' => $tag->name,
                        'type' => 'tag',
                        'popularity' => $tag->usage_count,
                        'metadata' => ['color' => $tag->color, 'id' => $tag->id]
                    ]);
                });
            
            $suggestions = $suggestions->merge($tagSuggestions);
            
            // Get recent search history suggestions
            $historySuggestions = SearchHistory::where('user_id', $userId)
                ->where('query', 'like', "%{$query}%")
                ->where('created_at', '>=', now()->subDays(30))
                ->orderByDesc('created_at')
                ->limit($limit - $suggestions->count())
                ->get()
                ->map(function ($history) {
                    return new SearchSuggestion([
                        'suggestion' => $history->query,
                        'type' => 'history',
                        'popularity' => 1,
                        'metadata' => ['timestamp' => $history->created_at]
                    ]);
                });
            
            $suggestions = $suggestions->merge($historySuggestions);
            
            return $suggestions->take($limit);
        });
    }
    
    /**
     * Get search history for user
     */
    public function getSearchHistory(int $limit = 20): Collection
    {
        $userId = Auth::id();
        
        return SearchHistory::where('user_id', $userId)
            ->where('created_at', '>=', now()->subDays(90))
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }
    
    /**
     * Get search analytics
     */
    public function getSearchAnalytics(int $days = 30): array
    {
        $userId = Auth::id();
        
        $startDate = now()->subDays($days);
        
        $analytics = [
            'total_searches' => SearchHistory::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'average_results' => SearchHistory::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->avg('results_count') ?? 0,
            
            'click_through_rate' => SearchHistory::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->where('clicked_result', true)
                ->count() / max(SearchHistory::where('user_id', $userId)
                    ->where('created_at', '>=', $startDate)
                    ->count(), 1) * 100,
            
            'top_queries' => SearchHistory::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->select('query', DB::raw('count(*) as count'))
                ->groupBy('query')
                ->orderByDesc('count')
                ->limit(10)
                ->get(),
            
            'search_trends' => SearchHistory::where('user_id', $userId)
                ->where('created_at', '>=', $startDate)
                ->select(DB::raw('DATE(created_at) as date'), DB::raw('count(*) as count'))
                ->groupBy('date')
                ->orderBy('date')
                ->get(),
        ];
        
        return $analytics;
    }
    
    /**
     * Record search result click
     */
    public function recordClick(string $query, int $resultId, string $resultType, int $position): void
    {
        try {
            $userId = Auth::id();
            
            // Update search history
            SearchHistory::where('user_id', $userId)
                ->where('query', $query)
                ->latest()
                ->first()
                ?->update(['clicked_result' => true]);
            
            // Update search analytics
            $queryHash = md5(strtolower(trim($query)));
            $analytics = SearchAnalytics::firstOrCreate(
                ['query_hash' => $queryHash],
                ['query' => $query]
            );
            
            $analytics->increment('click_count');
            $analytics->avg_click_position = ($analytics->avg_click_position * ($analytics->click_count - 1) + $position) / $analytics->click_count;
            $analytics->save();
            
        } catch (\Exception $e) {
            Log::error('Failed to record search click', [
                'query' => $query,
                'result_id' => $resultId,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Build search results
     */
    private function buildSearchResults(string $query, array $keywords, array $filters, int $perPage, int $page): array
    {
        $userId = Auth::id();
        $results = [];
        $totalCount = 0;
        
        // Search articles
        $articleResults = $this->searchArticles($query, $keywords, $filters, $userId, $perPage, $page);
        
        // Search notes
        $noteResults = $this->searchNotes($query, $keywords, $filters, $userId, $perPage, $page);
        
        // Combine and rank results
        $combinedResults = $this->combineAndRankResults($articleResults, $noteResults, $query, $keywords);
        
        // Apply pagination
        $offset = ($page - 1) * $perPage;
        $paginatedResults = $combinedResults->slice($offset, $perPage)->values();
        
        return [
            'results' => $paginatedResults,
            'total_count' => $combinedResults->count(),
            'article_count' => $articleResults['count'],
            'note_count' => $noteResults['count'],
            'current_page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($combinedResults->count() / $perPage),
            'query' => $query,
            'keywords' => $keywords,
            'filters' => $filters
        ];
    }
    
    /**
     * Search articles
     */
    private function searchArticles(string $query, array $keywords, array $filters, int $userId, int $perPage, int $page): array
    {
        $articleQuery = Article::where('user_id', $userId)
            ->with(['tags', 'user'])
            ->withCount(['notes', 'bookmarks']);
        
        // Apply full-text search
        if (!empty($query)) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'pgsql') {
                $articleQuery->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$query])
                    ->orWhereRaw("search_vector @@ to_tsquery('english', ?)", [implode(' & ', $keywords)]);
            } else {
                // MySQL full-text search
                $articleQuery->where(function ($q) use ($query, $keywords) {
                    $q->whereRaw("MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE)", [$query])
                      ->orWhereRaw("MATCH(title, content, excerpt) AGAINST(? IN BOOLEAN MODE)", [implode(' ', $keywords)]);
                });
            }
        }
        
        // Apply filters
        if (isset($filters['status'])) {
            $articleQuery->where('status', $filters['status']);
        }
        
        if (isset($filters['tags']) && !empty($filters['tags'])) {
            $articleQuery->whereHas('tags', function ($q) use ($filters) {
                $q->whereIn('tags.id', $filters['tags']);
            });
        }
        
        if (isset($filters['date_from'])) {
            $articleQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $articleQuery->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        if (isset($filters['domain'])) {
            $articleQuery->where('domain', 'like', "%{$filters['domain']}%");
        }
        
        // Get results
        $articles = $articleQuery->orderByDesc('created_at')
            ->limit($perPage * 3) // Get more results for better ranking
            ->get();
        
        // Score and rank results
        $scoredArticles = $articles->map(function ($article) use ($query, $keywords) {
            $score = $this->calculateArticleScore($article, $query, $keywords);
            $article->search_score = $score;
            $article->result_type = 'article';
            return $article;
        })->sortByDesc('search_score');
        
        return [
            'results' => $scoredArticles->values(),
            'count' => $scoredArticles->count()
        ];
    }
    
    /**
     * Search notes
     */
    private function searchNotes(string $query, array $keywords, array $filters, int $userId, int $perPage, int $page): array
    {
        $noteQuery = Note::where('user_id', $userId)
            ->with(['article', 'article.tags']);
        
        // Apply full-text search
        if (!empty($query)) {
            $driver = DB::connection()->getDriverName();
            
            if ($driver === 'pgsql') {
                $noteQuery->whereRaw("search_vector @@ plainto_tsquery('english', ?)", [$query])
                    ->orWhereRaw("search_vector @@ to_tsquery('english', ?)", [implode(' & ', $keywords)]);
            } else {
                // MySQL full-text search
                $noteQuery->whereRaw("MATCH(content) AGAINST(? IN BOOLEAN MODE)", [$query])
                    ->orWhereRaw("MATCH(content) AGAINST(? IN BOOLEAN MODE)", [implode(' ', $keywords)]);
            }
        }
        
        // Apply filters
        if (isset($filters['article_id'])) {
            $noteQuery->where('article_id', $filters['article_id']);
        }
        
        if (isset($filters['date_from'])) {
            $noteQuery->whereDate('created_at', '>=', $filters['date_from']);
        }
        
        if (isset($filters['date_to'])) {
            $noteQuery->whereDate('created_at', '<=', $filters['date_to']);
        }
        
        // Get results
        $notes = $noteQuery->orderByDesc('created_at')
            ->limit($perPage * 2) // Get fewer notes than articles
            ->get();
        
        // Score and rank results
        $scoredNotes = $notes->map(function ($note) use ($query, $keywords) {
            $score = $this->calculateNoteScore($note, $query, $keywords);
            $note->search_score = $score;
            $note->result_type = 'note';
            return $note;
        })->sortByDesc('search_score');
        
        return [
            'results' => $scoredNotes->values(),
            'count' => $scoredNotes->count()
        ];
    }
    
    /**
     * Calculate article search score
     */
    private function calculateArticleScore($article, string $query, array $keywords): float
    {
        $score = 0.0;
        $queryLower = strtolower($query);
        
        // Title matches (highest weight)
        if (stripos($article->title, $query) !== false) {
            $score += 10.0;
        }
        
        // Exact title match
        if (strtolower($article->title) === $queryLower) {
            $score += 5.0;
        }
        
        // Keyword matches in title
        foreach ($keywords as $keyword) {
            if (stripos($article->title, $keyword) !== false) {
                $score += 2.0;
            }
        }
        
        // Content matches
        if (stripos($article->content, $query) !== false) {
            $score += 3.0;
        }
        
        // Keyword matches in content
        $contentLower = strtolower(strip_tags($article->content));
        foreach ($keywords as $keyword) {
            $keywordCount = substr_count($contentLower, strtolower($keyword));
            $score += ($keywordCount * 0.5);
        }
        
        // Excerpt matches
        if ($article->excerpt && stripos($article->excerpt, $query) !== false) {
            $score += 2.0;
        }
        
        // Tag matches
        foreach ($article->tags as $tag) {
            if (stripos($tag->name, $query) !== false) {
                $score += 1.5;
            }
        }
        
        // Recency bonus
        $daysSinceCreation = now()->diffInDays($article->created_at);
        if ($daysSinceCreation <= 7) {
            $score += 1.0;
        } elseif ($daysSinceCreation <= 30) {
            $score += 0.5;
        }
        
        // Popularity bonus
        $score += ($article->view_count * 0.01);
        $score += ($article->notes_count * 0.1);
        $score += ($article->bookmarks_count * 0.2);
        
        return min($score, 100.0); // Cap at 100
    }
    
    /**
     * Calculate note search score
     */
    private function calculateNoteScore($note, string $query, array $keywords): float
    {
        $score = 0.0;
        $queryLower = strtolower($query);
        
        // Content matches
        if (stripos($note->content, $query) !== false) {
            $score += 5.0;
        }
        
        // Keyword matches in content
        $contentLower = strtolower(strip_tags($note->content));
        foreach ($keywords as $keyword) {
            $keywordCount = substr_count($contentLower, strtolower($keyword));
            $score += ($keywordCount * 0.3);
        }
        
        // Article title matches (if note belongs to article)
        if ($note->article) {
            if (stripos($note->article->title, $query) !== false) {
                $score += 2.0;
            }
            
            // Article tag matches
            foreach ($note->article->tags as $tag) {
                if (stripos($tag->name, $query) !== false) {
                    $score += 0.5;
                }
            }
        }
        
        // Recency bonus
        $daysSinceCreation = now()->diffInDays($note->created_at);
        if ($daysSinceCreation <= 3) {
            $score += 0.5;
        }
        
        return min($score, 50.0); // Cap at 50 (lower than articles)
    }
    
    /**
     * Combine and rank results from different sources
     */
    private function combineAndRankResults(array $articleResults, array $noteResults, string $query, array $keywords): Collection
    {
        $combined = collect();
        
        // Add articles
        foreach ($articleResults['results'] as $article) {
            $combined->push($article);
        }
        
        // Add notes
        foreach ($noteResults['results'] as $note) {
            $combined->push($note);
        }
        
        // Sort by search score
        return $combined->sortByDesc('search_score')->values();
    }
    
    /**
     * Process search query
     */
    private function processQuery(string $query): string
    {
        // Remove extra whitespace
        $query = preg_replace('/\s+/', ' ', trim($query));
        
        // Remove special characters that might interfere with search
        $query = preg_replace('/[^a-zA-Z0-9\s\-\_]/', '', $query);
        
        return $query;
    }
    
    /**
     * Extract keywords from query
     */
    private function extractKeywords(string $query): array
    {
        $words = explode(' ', strtolower($query));
        $keywords = [];
        
        foreach ($words as $word) {
            $word = trim($word);
            if (strlen($word) >= 2 && !in_array($word, $this->stopWords)) {
                $keywords[] = $word;
            }
        }
        
        return $keywords;
    }
    
    /**
     * Generate cache key
     */
    private function generateCacheKey(string $query, array $filters, int $userId, int $perPage, int $page): string
    {
        $filterString = json_encode($filters);
        return "search_{$userId}_" . md5("{$query}_{$filterString}_{$perPage}_{$page}");
    }
    
    /**
     * Record search history
     */
    private function recordSearchHistory(string $query, array $filters, int $resultsCount): void
    {
        try {
            SearchHistory::create([
                'user_id' => Auth::id(),
                'query' => $query,
                'filters' => $filters,
                'results_count' => $resultsCount,
                'session_id' => session()->getId(),
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Failed to record search history', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Update search analytics
     */
    private function updateSearchAnalytics(string $query, array $keywords): void
    {
        try {
            $queryHash = md5(strtolower(trim($query)));
            
            $analytics = SearchAnalytics::firstOrCreate(
                ['query_hash' => $queryHash],
                [
                    'query' => $query,
                    'keywords' => $keywords
                ]
            );
            
            $analytics->increment('search_count');
            
            // Update related queries
            $relatedQueries = $analytics->related_queries ?? [];
            foreach ($keywords as $keyword) {
                if (isset($relatedQueries[$keyword])) {
                    $relatedQueries[$keyword]++;
                } else {
                    $relatedQueries[$keyword] = 1;
                }
            }
            arsort($relatedQueries);
            $analytics->related_queries = array_slice($relatedQueries, 0, 10, true);
            
            $analytics->save();
            
        } catch (\Exception $e) {
            Log::error('Failed to update search analytics', [
                'query' => $query,
                'error' => $e->getMessage()
            ]);
        }
    }
    
    /**
     * Get empty search results
     */
    private function getEmptyResults(): array
    {
        return [
            'results' => collect(),
            'total_count' => 0,
            'article_count' => 0,
            'note_count' => 0,
            'current_page' => 1,
            'per_page' => 20,
            'total_pages' => 0,
            'query' => '',
            'keywords' => [],
            'filters' => []
        ];
    }
}
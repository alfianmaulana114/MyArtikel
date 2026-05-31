<?php

namespace App\Http\Controllers;

use App\Services\SearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class SearchController extends Controller
{
    protected SearchService $searchService;

    public function __construct(SearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Perform search across articles and notes
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:500',
            'filters' => 'nullable|array',
            'filters.status' => 'nullable|in:draft,published,archived',
            'filters.tags' => 'nullable|array',
            'filters.tags.*' => 'integer|exists:tags,id',
            'filters.date_from' => 'nullable|date',
            'filters.date_to' => 'nullable|date',
            'filters.domain' => 'nullable|string|max:255',
            'filters.article_id' => 'nullable|integer|exists:articles,id',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
            'type' => 'nullable|in:all,articles,notes',
        ]);

        try {
            $query = $validated['query'];
            $filters = $validated['filters'] ?? [];
            $perPage = $validated['per_page'] ?? 20;
            $page = $validated['page'] ?? 1;
            $type = $validated['type'] ?? 'all';

            // Add type filter if specified
            if ($type !== 'all') {
                $filters['type'] = $type;
            }

            // Perform search
            $results = $this->searchService->search($query, $filters, $perPage, $page);

            // Add search metadata
            $results['search_metadata'] = [
                'execution_time' => microtime(true) - LARAVEL_START,
                'cache_hit' => false,
                'query_type' => $this->determineQueryType($query),
                'suggestions_available' => $this->hasSuggestions($query),
            ];

            return response()->json($results);

        } catch (\Exception $e) {
            Log::error('Search error', [
                'query' => $request->input('query'),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => 'Search failed. Please try again.',
            ], 500);
        }
    }

    /**
     * Get search suggestions
     */
    public function suggestions(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:2|max:100',
            'limit' => 'nullable|integer|min:1|max:20',
        ]);

        try {
            $query = $validated['query'];
            $limit = $validated['limit'] ?? 10;

            $suggestions = $this->searchService->getSuggestions($query, $limit);

            return response()->json([
                'query' => $query,
                'suggestions' => $suggestions,
                'count' => $suggestions->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Suggestions error', [
                'query' => $query,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get suggestions',
                'suggestions' => [],
            ], 500);
        }
    }

    /**
     * Get search history
     */
    public function history(Request $request)
    {
        try {
            $limit = $request->input('limit', 20);
            $history = $this->searchService->getSearchHistory($limit);

            return response()->json([
                'history' => $history,
                'count' => $history->count(),
            ]);

        } catch (\Exception $e) {
            Log::error('Search history error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get search history',
                'history' => [],
            ], 500);
        }
    }

    /**
     * Get search analytics
     */
    public function analytics(Request $request)
    {
        try {
            $days = $request->input('days', 30);
            $analytics = $this->searchService->getSearchAnalytics($days);

            return response()->json([
                'analytics' => $analytics,
                'period' => "last {$days} days",
            ]);

        } catch (\Exception $e) {
            Log::error('Search analytics error', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to get search analytics',
                'analytics' => [],
            ], 500);
        }
    }

    /**
     * Record search result click
     */
    public function recordClick(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|max:500',
            'result_id' => 'required|integer',
            'result_type' => 'required|in:article,note',
            'position' => 'required|integer|min:1',
        ]);

        try {
            $this->searchService->recordClick(
                $validated['query'],
                $validated['result_id'],
                $validated['result_type'],
                $validated['position']
            );

            return response()->json([
                'message' => 'Click recorded successfully',
            ]);

        } catch (\Exception $e) {
            Log::error('Record click error', [
                'query' => $validated['query'],
                'result_id' => $validated['result_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Failed to record click',
            ], 500);
        }
    }

    /**
     * Quick search for instant results
     */
    public function quickSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'required|string|min:1|max:100',
            'limit' => 'nullable|integer|min:1|max:10',
        ]);

        try {
            $query = $validated['query'];
            $limit = $validated['limit'] ?? 5;

            // Use cache for quick search
            $cacheKey = "quick_search_{$query}_".Auth::id();
            $results = Cache::remember($cacheKey, 60, function () use ($query, $limit) {
                return $this->searchService->search($query, [], $limit, 1);
            });

            return response()->json([
                'query' => $query,
                'results' => $results['results'],
                'total_count' => $results['total_count'],
                'quick_search' => true,
            ]);

        } catch (\Exception $e) {
            Log::error('Quick search error', [
                'query' => $validated['query'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Quick search failed',
                'results' => [],
            ], 500);
        }
    }

    /**
     * Advanced search with multiple criteria
     */
    public function advancedSearch(Request $request)
    {
        $validated = $request->validate([
            'query' => 'nullable|string|max:500',
            'title' => 'nullable|string|max:255',
            'content' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'integer|exists:tags,id',
            'status' => 'nullable|in:draft,published,archived',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date',
            'domain' => 'nullable|string|max:255',
            'has_notes' => 'nullable|boolean',
            'has_bookmarks' => 'nullable|boolean',
            'sort_by' => 'nullable|in:relevance,date,title,popularity',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'page' => 'nullable|integer|min:1',
        ]);

        try {
            $filters = array_filter([
                'title' => $validated['title'] ?? null,
                'content' => $validated['content'] ?? null,
                'tags' => $validated['tags'] ?? null,
                'status' => $validated['status'] ?? null,
                'date_from' => $validated['date_from'] ?? null,
                'date_to' => $validated['date_to'] ?? null,
                'domain' => $validated['domain'] ?? null,
                'has_notes' => $validated['has_notes'] ?? null,
                'has_bookmarks' => $validated['has_bookmarks'] ?? null,
                'sort_by' => $validated['sort_by'] ?? 'relevance',
                'sort_order' => $validated['sort_order'] ?? 'desc',
            ]);

            $query = $validated['query'] ?? '';
            $perPage = $validated['per_page'] ?? 20;
            $page = $validated['page'] ?? 1;

            $results = $this->searchService->search($query, $filters, $perPage, $page);

            return response()->json([
                'advanced_search' => true,
                'filters_applied' => $filters,
                'results' => $results,
            ]);

        } catch (\Exception $e) {
            Log::error('Advanced search error', [
                'filters' => $validated,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'error' => 'Advanced search failed',
            ], 500);
        }
    }

    /**
     * Determine query type
     */
    private function determineQueryType(string $query): string
    {
        $query = strtolower(trim($query));

        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $query)) {
            return 'date';
        }

        if (preg_match('/^tag:/', $query)) {
            return 'tag';
        }

        if (preg_match('/^status:/', $query)) {
            return 'status';
        }

        if (strlen($query) <= 3) {
            return 'short';
        }

        if (preg_match('/\s+/', $query)) {
            return 'phrase';
        }

        return 'keyword';
    }

    /**
     * Check if suggestions are available
     */
    private function hasSuggestions(string $query): bool
    {
        return strlen($query) >= 2;
    }
}

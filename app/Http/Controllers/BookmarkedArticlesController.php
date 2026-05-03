<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class BookmarkedArticlesController extends Controller
{
    /**
     * Get bookmarked articles with filtering and sorting options.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'is_favorite' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_read' => 'nullable|boolean',
            'has_reminder' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,read_at,priority,title,category',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_article_details' => 'nullable|boolean',
            'include_category_details' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $userId = auth()->id();
        $includeArticleDetails = $request->get('include_article_details', true);
        $includeCategoryDetails = $request->get('include_category_details', true);

        // Build query with filters
        $query = Bookmark::forUser($userId)
            ->when($includeArticleDetails, function ($q) {
                return $q->with(['article' => function ($query) {
                    $query->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'reading_time', 'created_at', 'updated_at');
                }]);
            })
            ->when($includeCategoryDetails, function ($q) {
                return $q->with(['category' => function ($query) {
                    $query->select('id', 'name', 'color', 'description');
                }]);
            });

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results to include article details
        $articles = $this->transformResults($bookmarks, $request);

        // Get filter options for frontend
        $filterOptions = $this->getFilterOptions($userId);

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ],
                'filter_options' => $filterOptions,
                'stats' => $this->getStats($userId, $request)
            ]
        ]);
    }

    /**
     * Get bookmarked articles by category.
     */
    public function byCategory(Request $request, BookmarkCategory $category): JsonResponse
    {
        $this->authorize('view', $category);

        $validator = Validator::make($request->all(), [
            'is_favorite' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_read' => 'nullable|boolean',
            'priority' => 'nullable|integer|min:0|max:5',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,read_at,priority,title',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
            'include_article_details' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $includeArticleDetails = $request->get('include_article_details', true);

        $query = Bookmark::forUser(auth()->id())
            ->inCategory($category->id)
            ->when($includeArticleDetails, function ($q) {
                return $q->with(['article' => function ($query) {
                    $query->select('id', 'title', 'slug', 'excerpt', 'featured_image', 'reading_time', 'created_at', 'updated_at');
                }]);
            });

        // Apply filters
        $this->applyFilters($query, $request);

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results
        $articles = $this->transformResults($bookmarks, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ]
            ]
        ]);
    }

    /**
     * Get favorite bookmarked articles.
     */
    public function favorites(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'is_read' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,read_at,priority,title',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Bookmark::forUser(auth()->id())
            ->favorites()
            ->with(['article', 'category'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->inCategory($request->category_id);
            })
            ->when($request->has('is_read'), function ($q) use ($request) {
                return $request->is_read ? $q->read() : $q->unread();
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->search($request->search);
            });

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results
        $articles = $this->transformResults($bookmarks, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ]
            ]
        ]);
    }

    /**
     * Get archived bookmarked articles.
     */
    public function archived(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,archived_at,title',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Bookmark::forUser(auth()->id())
            ->archived()
            ->with(['article', 'category'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->inCategory($request->category_id);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->search($request->search);
            });

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results
        $articles = $this->transformResults($bookmarks, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ]
            ]
        ]);
    }

    /**
     * Get articles with due reminders.
     */
    public function dueReminders(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,reminder_at,title',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Bookmark::forUser(auth()->id())
            ->dueReminders()
            ->with(['article', 'category'])
            ->when($request->category_id, function ($q) use ($request) {
                return $q->inCategory($request->category_id);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->search($request->search);
            });

        // Apply sorting
        $this->applySorting($query, $request);

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results
        $articles = $this->transformResults($bookmarks, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ]
            ]
        ]);
    }

    /**
     * Search bookmarked articles.
     */
    public function search(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'query' => 'required|string|min:2|max:255',
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'is_favorite' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'is_read' => 'nullable|boolean',
            'per_page' => 'nullable|integer|min:1|max:100'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = Bookmark::forUser(auth()->id())
            ->with(['article', 'category'])
            ->search($request->query)
            ->when($request->category_id, function ($q) use ($request) {
                return $q->inCategory($request->category_id);
            })
            ->when($request->has('is_favorite'), function ($q) use ($request) {
                return $q->where('is_favorite', $request->is_favorite);
            })
            ->when($request->has('is_archived'), function ($q) use ($request) {
                return $q->where('is_archived', $request->is_archived);
            })
            ->when($request->has('is_read'), function ($q) use ($request) {
                return $request->is_read ? $q->read() : $q->unread();
            });

        // Sort by relevance (search score) first, then by created_at
        $query->orderBy('created_at', 'desc');

        // Paginate results
        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Transform results
        $articles = $this->transformResults($bookmarks, $request);

        return response()->json([
            'success' => true,
            'data' => [
                'articles' => $articles,
                'search_query' => $request->query,
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem()
                ]
            ]
        ]);
    }

    /**
     * Apply filters to the query.
     */
    private function applyFilters($query, Request $request): void
    {
        // Category filter
        if ($request->filled('category_id')) {
            $query->inCategory($request->category_id);
        }

        // Favorite filter
        if ($request->has('is_favorite')) {
            $query->where('is_favorite', $request->is_favorite);
        }

        // Archive filter
        if ($request->has('is_archived')) {
            $query->where('is_archived', $request->is_archived);
        }

        // Read status filter
        if ($request->has('is_read')) {
            $request->is_read ? $query->read() : $query->unread();
        }

        // Reminder filter
        if ($request->has('has_reminder')) {
            $request->has_reminder ? $query->withReminders() : $query->whereNull('reminder_at');
        }

        // Priority filter
        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        // Tags filter
        if ($request->filled('tags')) {
            foreach ($request->tags as $tag) {
                $query->whereJsonContains('tags', $tag);
            }
        }

        // Search filter
        if ($request->filled('search')) {
            $query->search($request->search);
        }
    }

    /**
     * Apply sorting to the query.
     */
    private function applySorting($query, Request $request): void
    {
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        switch ($sortBy) {
            case 'title':
                $query->join('articles', 'bookmarks.article_id', '=', 'articles.id')
                      ->orderBy('articles.title', $sortOrder)
                      ->select('bookmarks.*');
                break;
            case 'category':
                $query->join('bookmark_categories', 'bookmarks.category_id', '=', 'bookmark_categories.id')
                      ->orderBy('bookmark_categories.name', $sortOrder)
                      ->select('bookmarks.*');
                break;
            default:
                $query->orderBy($sortBy, $sortOrder);
        }
    }

    /**
     * Transform bookmark results to article format.
     */
    private function transformResults($bookmarks, Request $request): array
    {
        return $bookmarks->map(function ($bookmark) {
            return [
                'id' => $bookmark->article->id ?? null,
                'title' => $bookmark->article->title ?? 'Unknown Article',
                'slug' => $bookmark->article->slug ?? null,
                'excerpt' => $bookmark->article->excerpt ?? null,
                'featured_image' => $bookmark->article->featured_image ?? null,
                'reading_time' => $bookmark->article->reading_time ?? null,
                'article_created_at' => $bookmark->article->created_at ?? null,
                'article_updated_at' => $bookmark->article->updated_at ?? null,
                'bookmark_id' => $bookmark->id,
                'bookmark_created_at' => $bookmark->created_at,
                'bookmark_updated_at' => $bookmark->updated_at,
                'is_favorite' => $bookmark->is_favorite,
                'is_archived' => $bookmark->is_archived,
                'is_read' => !is_null($bookmark->read_at),
                'read_at' => $bookmark->read_at,
                'read_count' => $bookmark->read_count,
                'priority' => $bookmark->priority,
                'notes' => $bookmark->notes,
                'tags' => $bookmark->tags,
                'reminder_at' => $bookmark->reminder_at,
                'category' => $bookmark->category ? [
                    'id' => $bookmark->category->id,
                    'name' => $bookmark->category->name,
                    'color' => $bookmark->category->color,
                    'description' => $bookmark->category->description
                ] : null,
                'time_since_bookmarked' => $bookmark->time_since_created,
                'has_reminder' => $bookmark->has_reminder,
                'is_reminder_due' => $bookmark->is_reminder_due
            ];
        })->toArray();
    }

    /**
     * Get filter options for frontend.
     */
    private function getFilterOptions(int $userId): array
    {
        return [
            'categories' => BookmarkCategory::forUser($userId)
                ->orderBy('position')
                ->orderBy('name')
                ->get(['id', 'name', 'color', 'description']),
            'tags' => $this->getAllTags($userId),
            'priorities' => [0, 1, 2, 3, 4, 5],
            'statuses' => [
                ['value' => 'read', 'label' => 'Read'],
                ['value' => 'unread', 'label' => 'Unread'],
                ['value' => 'favorite', 'label' => 'Favorite'],
                ['value' => 'archived', 'label' => 'Archived']
            ]
        ];
    }

    /**
     * Get all unique tags from user's bookmarks.
     */
    private function getAllTags(int $userId): array
    {
        $bookmarks = Bookmark::forUser($userId)->whereNotNull('tags')->get();
        $allTags = [];
        
        foreach ($bookmarks as $bookmark) {
            if (is_array($bookmark->tags)) {
                $allTags = array_merge($allTags, $bookmark->tags);
            }
        }
        
        return array_unique(array_filter(array_map('trim', $allTags)));
    }

    /**
     * Get statistics for filtered results.
     */
    private function getStats(int $userId, Request $request): array
    {
        $query = Bookmark::forUser($userId);
        $this->applyFilters($query, $request);
        
        $totalFiltered = $query->count();
        $totalBookmarks = Bookmark::forUser($userId)->count();
        
        return [
            'total_filtered' => $totalFiltered,
            'total_bookmarks' => $totalBookmarks,
            'read_count' => (clone $query)->read()->count(),
            'unread_count' => (clone $query)->unread()->count(),
            'favorite_count' => (clone $query)->favorites()->count(),
            'archived_count' => (clone $query)->archived()->count(),
            'with_reminders_count' => (clone $query)->withReminders()->count(),
            'due_reminders_count' => (clone $query)->dueReminders()->count()
        ];
    }
}
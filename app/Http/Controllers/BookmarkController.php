<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\BookmarkAnalytics;
use App\Models\BookmarkCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookmarkController extends Controller
{
    /**
     * Get all bookmarks for the authenticated user.
     */
    public function index(Request $request)
    {
        return view('bookmarks.index');
    }

    public function data(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'is_favorite' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'search' => 'nullable|string|max:255',
            'sort_by' => 'nullable|in:created_at,updated_at,priority,read_at,title',
            'sort_order' => 'nullable|in:asc,desc',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $query = Bookmark::with(['article', 'category'])
            ->forUser(auth()->id())
            ->when($request->category_id, function ($q) use ($request) {
                return $q->inCategory($request->category_id);
            })
            ->when($request->has('is_favorite'), function ($q) use ($request) {
                return $q->where('is_favorite', $request->is_favorite);
            })
            ->when($request->has('is_archived'), function ($q) use ($request) {
                return $q->where('is_archived', $request->is_archived);
            })
            ->when($request->search, function ($q) use ($request) {
                return $q->search($request->search);
            });

        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');

        // Handle sorting by article title
        if ($sortBy === 'title') {
            $query->join('articles', 'bookmarks.article_id', '=', 'articles.id')
                ->orderBy('articles.title', $sortOrder)
                ->select('bookmarks.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        // Record analytics
        $this->recordAnalytics('bookmarks_viewed', [
            'filters' => $request->only(['category_id', 'is_favorite', 'is_archived', 'search']),
            'count' => $bookmarks->total(),
        ]);

        return response()->json([
            'success' => true,
            'data' => [
                'bookmarks' => $bookmarks->items(),
                'pagination' => [
                    'current_page' => $bookmarks->currentPage(),
                    'last_page' => $bookmarks->lastPage(),
                    'per_page' => $bookmarks->perPage(),
                    'total' => $bookmarks->total(),
                    'from' => $bookmarks->firstItem(),
                    'to' => $bookmarks->lastItem(),
                ],
                'stats' => $this->getBookmarkStats(),
            ],
        ]);
    }

    /**
     * Store a new bookmark.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'article_id' => 'required|exists:articles,id',
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'priority' => 'nullable|integer|min:0|max:5',
            'reminder_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        // Check if bookmark already exists (with lock to prevent race condition)
        $existingBookmark = Bookmark::where('user_id', auth()->id())
            ->where('article_id', $request->article_id)
            ->lockForUpdate()
            ->first();

        if ($existingBookmark) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Article already bookmarked',
                'bookmark' => $existingBookmark->load(['article', 'category']),
            ], 409);
        }

        // Verify category ownership if provided
        if ($request->category_id) {
            $category = BookmarkCategory::where('user_id', auth()->id())
                ->where('id', $request->category_id)
                ->first();

            if (! $category) {
                DB::rollBack();

                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or not owned by user',
                ], 404);
            }
        }

        $bookmark = Bookmark::create([
            'user_id' => auth()->id(),
            'article_id' => $request->article_id,
            'category_id' => $request->category_id,
            'notes' => $request->notes,
            'tags' => $request->tags ?? [],
            'priority' => $request->priority ?? 0,
            'source_device' => $request->header('X-Device-Type', 'unknown'),
            'source_browser' => $request->header('User-Agent'),
            'created_ip' => $request->ip(),
            'position' => $this->getNextPosition(),
        ]);

        // Update article bookmark count
        Article::where('id', $request->article_id)->increment('bookmarks_count');

        // Update user bookmark count
        auth()->user()->increment('bookmarks_count');

        DB::commit();

        // Record analytics
        $this->recordAnalytics('bookmark_created', [
            'bookmark_id' => $bookmark->id,
            'article_id' => $request->article_id,
            'category_id' => $request->category_id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Article bookmarked successfully',
            'data' => $bookmark->load(['article', 'category']),
        ], 201);
    }

    /**
     * Get a specific bookmark.
     */
    public function show(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('view', $bookmark);

        // Record analytics
        $this->recordAnalytics('bookmark_viewed', [
            'bookmark_id' => $bookmark->id,
        ]);

        return response()->json([
            'success' => true,
            'data' => $bookmark->load(['article', 'category', 'analytics']),
        ]);
    }

    /**
     * Update a bookmark.
     */
    public function update(Request $request, Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $validator = Validator::make($request->all(), [
            'category_id' => 'nullable|exists:bookmark_categories,id',
            'notes' => 'nullable|string|max:1000',
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'priority' => 'nullable|integer|min:0|max:5',
            'is_favorite' => 'nullable|boolean',
            'reminder_at' => 'nullable|date|after:now',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify category ownership if provided
        if ($request->category_id) {
            $category = BookmarkCategory::where('user_id', auth()->id())
                ->where('id', $request->category_id)
                ->first();

            if (! $category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or not owned by user',
                ], 404);
            }
        }

        $bookmark->update($request->only([
            'category_id', 'notes', 'tags', 'priority', 'is_favorite', 'reminder_at',
        ]));

        // Record analytics
        $this->recordAnalytics('bookmark_updated', [
            'bookmark_id' => $bookmark->id,
            'fields_updated' => array_keys($request->only([
                'category_id', 'notes', 'tags', 'priority', 'is_favorite', 'reminder_at',
            ])),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark updated successfully',
            'data' => $bookmark->load(['article', 'category']),
        ]);
    }

    /**
     * Delete a bookmark.
     */
    public function destroy(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('delete', $bookmark);

        DB::transaction(function () use ($bookmark) {
            $articleId = $bookmark->article_id;

            $bookmark->delete();

            // Update article bookmark count
            Article::where('id', $articleId)->decrement('bookmarks_count');

            // Update user bookmark count
            auth()->user()->decrement('bookmarks_count');
        });

        // Record analytics
        $this->recordAnalytics('bookmark_deleted', [
            'bookmark_id' => $bookmark->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark deleted successfully',
        ]);
    }

    /**
     * Toggle bookmark favorite status.
     */
    public function toggleFavorite(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $bookmark->toggleFavorite();

        // Record analytics
        $this->recordAnalytics('bookmark_favorite_toggled', [
            'bookmark_id' => $bookmark->id,
            'is_favorite' => $bookmark->is_favorite,
        ]);

        return response()->json([
            'success' => true,
            'message' => $bookmark->is_favorite ? 'Added to favorites' : 'Removed from favorites',
            'data' => ['is_favorite' => $bookmark->is_favorite],
        ]);
    }

    /**
     * Mark bookmark as read.
     */
    public function markAsRead(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $bookmark->markAsRead();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark marked as read',
            'data' => ['read_at' => $bookmark->read_at],
        ]);
    }

    /**
     * Mark bookmark as unread.
     */
    public function markAsUnread(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $bookmark->markAsUnread();

        return response()->json([
            'success' => true,
            'message' => 'Bookmark marked as unread',
            'data' => ['read_at' => null],
        ]);
    }

    /**
     * Archive a bookmark.
     */
    public function archive(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $bookmark->archive();

        // Record analytics
        $this->recordAnalytics('bookmark_archived', [
            'bookmark_id' => $bookmark->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark archived successfully',
            'data' => ['is_archived' => true],
        ]);
    }

    /**
     * Unarchive a bookmark.
     */
    public function unarchive(Bookmark $bookmark): JsonResponse
    {
        $this->authorize('update', $bookmark);

        $bookmark->unarchive();

        // Record analytics
        $this->recordAnalytics('bookmark_unarchived', [
            'bookmark_id' => $bookmark->id,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Bookmark unarchived successfully',
            'data' => ['is_archived' => false],
        ]);
    }

    /**
     * Check if an article is bookmarked.
     */
    public function checkArticle(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'article_id' => 'required|exists:articles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        $bookmark = Bookmark::with(['category'])
            ->where('user_id', auth()->id())
            ->where('article_id', $request->article_id)
            ->first();

        return response()->json([
            'success' => true,
            'data' => [
                'is_bookmarked' => ! is_null($bookmark),
                'bookmark' => $bookmark,
            ],
        ]);
    }

    /**
     * Bulk operations on bookmarks.
     */
    public function bulkOperation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'bookmark_ids' => 'required|array',
            'bookmark_ids.*' => 'exists:bookmarks,id',
            'operation' => 'required|in:delete,archive,unarchive,favorite,unfavorite,mark_read,mark_unread,category',
            'category_id' => 'required_if:operation,category|exists:bookmark_categories,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Verify ownership of all bookmarks
        $bookmarks = Bookmark::where('user_id', auth()->id())
            ->whereIn('id', $request->bookmark_ids)
            ->get();

        if ($bookmarks->count() !== count($request->bookmark_ids)) {
            return response()->json([
                'success' => false,
                'message' => 'Some bookmarks not found or not owned by user',
            ], 404);
        }

        // Verify category ownership if operation is category
        if ($request->operation === 'category' && $request->category_id) {
            $category = BookmarkCategory::where('user_id', auth()->id())
                ->where('id', $request->category_id)
                ->first();

            if (! $category) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category not found or not owned by user',
                ], 404);
            }
        }

        $affectedCount = 0;

        DB::transaction(function () use ($request, &$affectedCount) {
            $query = Bookmark::where('user_id', auth()->id())
                ->whereIn('id', $request->bookmark_ids);

            switch ($request->operation) {
                case 'delete':
                    $affectedCount = $query->count();
                    $articleIds = $query->pluck('article_id')->toArray();
                    $query->delete();

                    // Update counts
                    Article::whereIn('id', $articleIds)->decrement('bookmarks_count');
                    auth()->user()->decrement('bookmarks_count', $affectedCount);
                    break;

                case 'archive':
                    $affectedCount = $query->update(['is_archived' => true]);
                    break;

                case 'unarchive':
                    $affectedCount = $query->update(['is_archived' => false]);
                    break;

                case 'favorite':
                    $affectedCount = $query->update(['is_favorite' => true]);
                    break;

                case 'unfavorite':
                    $affectedCount = $query->update(['is_favorite' => false]);
                    break;

                case 'mark_read':
                    $affectedCount = $query->update(['read_at' => now()]);
                    break;

                case 'mark_unread':
                    $affectedCount = $query->update(['read_at' => null]);
                    break;

                case 'category':
                    $affectedCount = $query->update(['category_id' => $request->category_id]);
                    break;
            }
        });

        // Record analytics
        $this->recordAnalytics('bulk_operation', [
            'bookmark_id' => $request->bookmark_ids[0] ?? null,
            'operation' => $request->operation,
            'bookmark_count' => $affectedCount,
        ]);

        return response()->json([
            'success' => true,
            'message' => "{$affectedCount} bookmarks processed successfully",
            'data' => ['affected_count' => $affectedCount],
        ]);
    }

    /**
     * Get bookmark statistics.
     */
    private function getBookmarkStats(): array
    {
        $userId = auth()->id();

        return [
            'total' => Bookmark::forUser($userId)->count(),
            'favorites' => Bookmark::forUser($userId)->favorites()->count(),
            'unread' => Bookmark::forUser($userId)->unread()->count(),
            'archived' => Bookmark::forUser($userId)->archived()->count(),
            'with_reminders' => Bookmark::forUser($userId)->withReminders()->count(),
            'due_reminders' => Bookmark::forUser($userId)->dueReminders()->count(),
        ];
    }

    /**
     * Get next position for new bookmark.
     */
    private function getNextPosition(): int
    {
        return Bookmark::forUser(auth()->id())->max('position') + 1;
    }

    /**
     * Record analytics for the current user.
     */
    private function recordAnalytics(string $action, array $metadata = []): void
    {
        $bookmarkId = $metadata['bookmark_id'] ?? null;

        // Skip if no bookmark_id is available for actions that require it
        if ($bookmarkId === null) {
            return;
        }

        BookmarkAnalytics::create([
            'user_id' => auth()->id(),
            'bookmark_id' => $bookmarkId,
            'action' => $action,
            'metadata' => $metadata,
            'device_id' => request()->header('X-Device-ID'),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
    }
}

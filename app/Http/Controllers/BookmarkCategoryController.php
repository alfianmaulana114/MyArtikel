<?php

namespace App\Http\Controllers;

use App\Models\BookmarkCategory;
use App\Models\Bookmark;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookmarkCategoryController extends Controller
{
    /**
     * Get all bookmark categories for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'include_bookmarks_count' => 'nullable|boolean',
            'include_unread_count' => 'nullable|boolean',
            'sort_by' => 'nullable|in:position,name,created_at,bookmarks_count',
            'sort_order' => 'nullable|in:asc,desc'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $query = BookmarkCategory::forUser(auth()->id());

        $sortBy = $request->get('sort_by', 'position');
        $sortOrder = $request->get('sort_order', 'asc');

        if ($sortBy === 'bookmarks_count') {
            $query->withCount('bookmarks')
                  ->orderBy('bookmarks_count', $sortOrder);
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $categories = $query->get();

        // Include additional counts if requested
        if ($request->include_bookmarks_count || $request->include_unread_count) {
            $categories->each(function ($category) use ($request) {
                if ($request->include_bookmarks_count) {
                    $category->bookmarks_count = $category->bookmarks()->count();
                }
                if ($request->include_unread_count) {
                    $category->unread_bookmarks_count = $category->bookmarks()->unread()->count();
                }
            });
        }

        return response()->json([
            'success' => true,
            'data' => [
                'categories' => $categories,
                'total' => $categories->count()
            ]
        ]);
    }

    /**
     * Create a new bookmark category.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'color' => 'nullable|string|max:7|regex:/^#?[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
            'position' => 'nullable|integer|min:0',
            'is_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate name
        $existingCategory = BookmarkCategory::forUser(auth()->id())
            ->where('name', $request->name)
            ->first();

        if ($existingCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Category with this name already exists'
            ], 409);
        }

        $category = DB::transaction(function () use ($request) {
            $category = BookmarkCategory::create([
                'user_id' => auth()->id(),
                'name' => $request->name,
                'color' => $request->color ?? '#3b82f6',
                'description' => $request->description,
                'position' => $request->position ?? $this->getNextPosition(),
                'is_public' => $request->is_public ?? false,
                'is_default' => false
            ]);

            // Update user category count
            auth()->user()->increment('bookmark_categories_count');

            return $category;
        });

        return response()->json([
            'success' => true,
            'message' => 'Category created successfully',
            'data' => $category
        ], 201);
    }

    /**
     * Get a specific category.
     */
    public function show(BookmarkCategory $category): JsonResponse
    {
        $this->authorize('view', $category);

        $category->loadCount(['bookmarks', 'bookmarks as unread_bookmarks_count' => function ($query) {
            $query->unread();
        }]);

        return response()->json([
            'success' => true,
            'data' => $category
        ]);
    }

    /**
     * Update a category.
     */
    public function update(Request $request, BookmarkCategory $category): JsonResponse
    {
        $this->authorize('update', $category);

        // Cannot update default categories name
        if ($category->is_default && $request->has('name') && $request->name !== $category->name) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot rename default categories'
            ], 403);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:100',
            'color' => 'nullable|string|max:7|regex:/^#?[0-9A-Fa-f]{6}$/',
            'description' => 'nullable|string|max:500',
            'position' => 'nullable|integer|min:0',
            'is_public' => 'nullable|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Check for duplicate name (excluding current category)
        if ($request->has('name')) {
            $existingCategory = BookmarkCategory::forUser(auth()->id())
                ->where('name', $request->name)
                ->where('id', '!=', $category->id)
                ->first();

            if ($existingCategory) {
                return response()->json([
                    'success' => false,
                    'message' => 'Category with this name already exists'
                ], 409);
            }
        }

        $category->update($request->only(['name', 'color', 'description', 'position', 'is_public']));

        return response()->json([
            'success' => true,
            'message' => 'Category updated successfully',
            'data' => $category
        ]);
    }

    /**
     * Delete a category.
     */
    public function destroy(BookmarkCategory $category): JsonResponse
    {
        $this->authorize('delete', $category);

        if ($category->is_default) {
            return response()->json([
                'success' => false,
                'message' => 'Cannot delete default categories'
            ], 403);
        }

        DB::transaction(function () use ($category) {
            // Move bookmarks to default "Favorites" category
            $defaultCategory = BookmarkCategory::forUser(auth()->id())
                ->where('name', 'Favorites')
                ->where('is_default', true)
                ->first();

            if ($defaultCategory) {
                Bookmark::where('category_id', $category->id)
                    ->update(['category_id' => $defaultCategory->id]);
            }

            $category->delete();

            // Update user category count
            auth()->user()->decrement('bookmark_categories_count');
        });

        return response()->json([
            'success' => true,
            'message' => 'Category deleted successfully'
        ]);
    }

    /**
     * Reorder categories.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'categories' => 'required|array',
            'categories.*.id' => 'required|exists:bookmark_categories,id',
            'categories.*.position' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        // Verify ownership of all categories
        $categoryIds = collect($request->categories)->pluck('id');
        $ownedCategories = BookmarkCategory::forUser(auth()->id())
            ->whereIn('id', $categoryIds)
            ->pluck('id');

        if ($ownedCategories->count() !== count($categoryIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Some categories not found or not owned by user'
            ], 404);
        }

        DB::transaction(function () use ($request) {
            foreach ($request->categories as $categoryData) {
                BookmarkCategory::where('id', $categoryData['id'])
                    ->update(['position' => $categoryData['position']]);
            }
        });

        return response()->json([
            'success' => true,
            'message' => 'Categories reordered successfully'
        ]);
    }

    /**
     * Get bookmarks for a specific category.
     */
    public function bookmarks(Request $request, BookmarkCategory $category): JsonResponse
    {
        $this->authorize('view', $category);

        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'is_favorite' => 'nullable|boolean',
            'is_archived' => 'nullable|boolean',
            'sort_by' => 'nullable|in:created_at,updated_at,priority,read_at,title',
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

        $query = Bookmark::with(['article'])
            ->forUser(auth()->id())
            ->inCategory($category->id)
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

        if ($sortBy === 'title') {
            $query->join('articles', 'bookmarks.article_id', '=', 'articles.id')
                  ->orderBy('articles.title', $sortOrder)
                  ->select('bookmarks.*');
        } else {
            $query->orderBy($sortBy, $sortOrder);
        }

        $perPage = $request->get('per_page', 20);
        $bookmarks = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => [
                'category' => $category,
                'bookmarks' => $bookmarks->items(),
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
     * Get next position for new category.
     */
    private function getNextPosition(): int
    {
        return BookmarkCategory::forUser(auth()->id())->max('position') + 1;
    }
}
<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Tag;
use App\Services\TagSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ArticleController extends Controller
{
    protected TagSuggestionService $tagSuggestionService;
    
    public function __construct(TagSuggestionService $tagSuggestionService)
    {
        $this->tagSuggestionService = $tagSuggestionService;
    }
    
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        return view('articles.index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Article::query()->where('user_id', $user->id);
        
        // Filter by status
        if ($request->has('status')) {
            $query->byStatus($request->status);
        }
        
        // Filter by tags
        if ($request->has('tags')) {
            $tagIds = is_array($request->tags) ? $request->tags : explode(',', $request->tags);
            $query->whereHas('tags', function ($q) use ($tagIds) {
                $q->whereIn('tags.id', $tagIds);
            });
        }
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%")
                  ->orWhere('excerpt', 'like', "%{$search}%");
            });
        }
        
        // Filter by date range
        if ($request->has('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->has('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        // Pagination
        $articles = $query->with(['tags', 'user'])
            ->withCount(['notes', 'bookmarks'])
            ->paginate($request->get('per_page', 20));
        
        // Get tag statistics for filtering
        $tagStats = $this->getTagStatistics($user->id);
        
        return response()->json([
            'articles' => $articles,
            'tag_statistics' => $tagStats,
            'filters' => [
                'status' => $request->status,
                'tags' => $request->tags,
                'search' => $request->search,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ]
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'status' => ['required', Rule::in(['draft', 'published', 'archived'])],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'auto_generate_tags' => 'boolean',
            'published_at' => 'nullable|date',
        ]);
        
        $user = Auth::user();
        
        $article = Article::create([
            'title' => $validated['title'],
            'slug' => $this->generateUniqueSlug($validated['title'], $user->id),
            'content' => $validated['content'],
            'excerpt' => $validated['excerpt'] ?? $this->generateExcerpt($validated['content']),
            'featured_image' => $validated['featured_image'],
            'user_id' => $user->id,
            'status' => $validated['status'],
            'published_at' => $validated['published_at'],
        ]);
        
        // Handle tags
        $this->processTags($article, $validated['tags'] ?? [], $validated['auto_generate_tags'] ?? false);
        
        return response()->json([
            'message' => 'Article created successfully',
            'article' => $article->load(['tags', 'user']),
        ], 201);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $user = Auth::user();
        $article = Article::where('user_id', $user->id)
            ->with(['tags', 'user', 'notes', 'bookmarks'])
            ->withCount(['notes', 'bookmarks'])
            ->findOrFail($id);
        
        // Increment view count
        $article->increment('view_count');
        
        // Get related articles based on tags
        $relatedArticles = $this->getRelatedArticles($article, $user->id);
        
        // Get tag suggestions for this article
        $tagSuggestions = $this->tagSuggestionService->suggestTags(
            $article->content,
            $user->id,
            $article->id
        );

        if ($request->wantsJson()) {
            return response()->json([
                'article' => $article,
                'related_articles' => $relatedArticles,
                'tag_suggestions' => $tagSuggestions,
            ]);
        }

        return view('articles.show', [
            'article' => $article,
            'relatedArticles' => $relatedArticles,
            'tagSuggestions' => $tagSuggestions,
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'content' => 'sometimes|string',
            'excerpt' => 'nullable|string',
            'featured_image' => 'nullable|string|max:500',
            'status' => ['sometimes', Rule::in(['draft', 'published', 'archived'])],
            'tags' => 'nullable|array',
            'tags.*' => 'string|max:50',
            'auto_generate_tags' => 'boolean',
            'published_at' => 'nullable|date',
        ]);
        
        $user = Auth::user();
        $article = Article::where('user_id', $user->id)->findOrFail($id);
        
        // Update basic fields
        if (isset($validated['title'])) {
            $article->title = $validated['title'];
            $article->slug = $this->generateUniqueSlug($validated['title'], $user->id, $article->id);
        }
        
        if (isset($validated['content'])) {
            $article->content = $validated['content'];
            if (!isset($validated['excerpt'])) {
                $article->excerpt = $this->generateExcerpt($validated['content']);
            }
        }
        
        if (isset($validated['excerpt'])) {
            $article->excerpt = $validated['excerpt'];
        }
        
        if (isset($validated['featured_image'])) {
            $article->featured_image = $validated['featured_image'];
        }
        
        if (isset($validated['status'])) {
            $article->status = $validated['status'];
        }
        
        if (isset($validated['published_at'])) {
            $article->published_at = $validated['published_at'];
        }
        
        $article->save();
        
        // Handle tags
        if (isset($validated['tags']) || isset($validated['auto_generate_tags'])) {
            $this->processTags($article, $validated['tags'] ?? [], $validated['auto_generate_tags'] ?? false);
        }
        
        return response()->json([
            'message' => 'Article updated successfully',
            'article' => $article->load(['tags', 'user']),
        ]);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $article = Article::where('user_id', $user->id)->findOrFail($id);
        
        // Detach all tags before deletion
        $article->tags()->detach();
        
        $article->delete();
        
        return response()->json([
            'message' => 'Article deleted successfully'
        ]);
    }
    
    /**
     * Process tags for an article
     */
    private function processTags(Article $article, array $tags, bool $autoGenerate = false)
    {
        $tagIds = [];
        
        // Process provided tags
        foreach ($tags as $tagName) {
            $tagName = trim($tagName);
            if (empty($tagName)) continue;
            
            $tag = $this->findOrCreateTag($tagName, Auth::id());
            if ($tag) {
                $tagIds[] = $tag->id;
            }
        }
        
        // Auto-generate tags if requested
        if ($autoGenerate) {
            $suggestions = $this->tagSuggestionService->suggestTags(
                $article->content,
                Auth::id(),
                $article->id
            );
            
            foreach ($suggestions as $suggestion) {
                $tag = $suggestion['tag'];
                if (is_array($tag)) {
                    $tag = $this->findOrCreateTag($tag['name'], Auth::id(), true);
                } else {
                    $tag = $this->findOrCreateTag($tag->name, Auth::id(), true);
                }
                
                if ($tag && !in_array($tag->id, $tagIds)) {
                    $tagIds[] = $tag->id;
                }
            }
        }
        
        // Sync tags with article
        $article->tags()->sync($tagIds);
        
        // Update tag usage counts
        $this->updateTagUsageCounts($tagIds);
    }
    
    /**
     * Find or create a tag
     */
    private function findOrCreateTag(string $tagName, int $userId, bool $isAutoGenerated = false): ?Tag
    {
        // First try to find existing tag (user-specific or system)
        $tag = Tag::where(function ($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereNull('user_id');
            })
            ->where('name', $tagName)
            ->first();
        
        if ($tag) {
            return $tag;
        }
        
        // Create new tag
        try {
            return Tag::create([
                'name' => $tagName,
                'slug' => Str::slug($tagName),
                'user_id' => $userId,
                'type' => $isAutoGenerated ? 'auto' : 'custom',
                'is_auto_generated' => $isAutoGenerated,
                'color' => $this->generateRandomColor(),
            ]);
        } catch (\Exception $e) {
            // Handle duplicate slug case
            return Tag::where('user_id', $userId)
                ->where('name', $tagName)
                ->first();
        }
    }
    
    /**
     * Update tag usage counts
     */
    private function updateTagUsageCounts(array $tagIds): void
    {
        foreach ($tagIds as $tagId) {
            $tag = Tag::find($tagId);
            if ($tag) {
                $tag->articles_count = $tag->articles()->count();
                $tag->usage_count = $tag->articles()->where('user_id', Auth::id())->count();
                $tag->save();
            }
        }
    }
    
    /**
     * Generate unique slug
     */
    private function generateUniqueSlug(string $title, int $userId, ?int $excludeId = null): string
    {
        $slug = Str::slug($title);
        $originalSlug = $slug;
        $count = 1;
        
        while (Article::where('user_id', $userId)
            ->where('slug', $slug)
            ->when($excludeId, function ($query) use ($excludeId) {
                $query->where('id', '!=', $excludeId);
            })
            ->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }
        
        return $slug;
    }
    
    /**
     * Generate excerpt from content
     */
    private function generateExcerpt(string $content, int $length = 200): string
    {
        $text = strip_tags($content);
        $text = trim($text);
        
        if (strlen($text) <= $length) {
            return $text;
        }
        
        return substr($text, 0, $length) . '...';
    }
    
    /**
     * Get related articles based on tags
     */
    private function getRelatedArticles(Article $article, int $userId)
    {
        $articleTagIds = $article->tags->pluck('id')->toArray();
        
        if (empty($articleTagIds)) {
            return collect();
        }
        
        return Article::where('user_id', $userId)
            ->where('id', '!=', $article->id)
            ->where('status', 'published')
            ->whereHas('tags', function ($query) use ($articleTagIds) {
                $query->whereIn('tags.id', $articleTagIds);
            })
            ->with(['tags'])
            ->withCount('tags')
            ->orderByDesc('tags_count')
            ->orderByDesc('view_count')
            ->limit(5)
            ->get();
    }
    
    /**
     * Get tag statistics
     */
    private function getTagStatistics(int $userId): array
    {
        return [
            'total_tags' => Tag::forUser($userId)->count(),
            'most_used_tags' => Tag::forUser($userId)
                ->orderByDesc('usage_count')
                ->limit(10)
                ->get(),
            'articles_with_tags' => Article::where('user_id', $userId)
                ->whereHas('tags')
                ->count(),
            'total_tag_usage' => Tag::forUser($userId)->sum('usage_count'),
        ];
    }
    
    /**
     * Generate random color
     */
    private function generateRandomColor(): string
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
        ];
        
        return $colors[array_rand($colors)];
    }
}

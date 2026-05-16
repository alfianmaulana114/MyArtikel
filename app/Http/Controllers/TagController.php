<?php

namespace App\Http\Controllers;

use App\Http\Requests\TagRequest;
use App\Models\Tag;
use App\Services\TagSuggestionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class TagController extends Controller
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
        return view('tags.index');
    }

    public function data(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Tag::query();
        
        // Filter by user
        $query->where(function ($q) use ($user) {
            $q->where('user_id', $user->id)
              ->orWhereNull('user_id'); // Include system tags
        });
        
        // Filter by type
        if ($request->has('type')) {
            $query->where('type', $request->type);
        }
        
        // Search functionality
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        
        // Sort options
        $sortBy = $request->get('sort_by', 'usage_count');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);
        
        $tags = $query->paginate($request->get('per_page', 20));
        
        return response()->json([
            'tags' => $tags,
            'statistics' => $this->getTagStatistics($user->id)
        ]);
    }
    
    /**
     * Get tag suggestions based on content
     */
    public function suggest(Request $request)
    {
        $request->validate([
            'content' => 'required|string',
            'article_id' => 'nullable|exists:articles,id'
        ]);
        
        $suggestions = $this->tagSuggestionService->suggestTags(
            $request->content,
            Auth::id(),
            $request->article_id
        );
        
        return response()->json([
            'suggestions' => $suggestions
        ]);
    }
    
    /**
     * Autocomplete tags for input
     */
    public function autocomplete(Request $request)
    {
        $request->validate([
            'query' => 'required|string|min:1'
        ]);
        
        $user = Auth::user();
        $query = $request->input('query', '');
        
        $tags = Tag::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->where('name', 'like', "%{$query}%")
            ->orderBy('usage_count', 'desc')
            ->limit(10)
            ->get(['id', 'name', 'slug', 'color', 'description']);
        
        return response()->json([
            'tags' => $tags
        ]);
    }
    
    /**
     * Store a newly created resource in storage.
     */
    public function store(TagRequest $request)
    {
        $user = Auth::user();
        
        // Check if tag already exists for this user
        $existingTag = Tag::where('user_id', $user->id)
            ->where('name', $request->name)
            ->first();
            
        if ($existingTag) {
            return response()->json([
                'message' => 'Tag already exists',
                'tag' => $existingTag
            ], 422);
        }
        
        $tag = Tag::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
            'description' => $request->description,
            'color' => $request->color ?? $this->generateRandomColor(),
            'user_id' => $user->id,
            'type' => 'custom',
            'is_auto_generated' => false,
        ]);
        
        return response()->json([
            'message' => 'Tag created successfully',
            'tag' => $tag
        ], 201);
    }
    
    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = Auth::user();
        $tag = Tag::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->with(['articles' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->withCount('notes')
                      ->withCount('bookmarks');
            }])
            ->findOrFail($id);
        
        return response()->json([
            'tag' => $tag,
            'articles_count' => $tag->articles->count(),
            'related_tags' => $this->getRelatedTags($tag, $user->id)
        ]);
    }
    
    /**
     * Update the specified resource in storage.
     */
    public function update(TagRequest $request, string $id)
    {
        $user = Auth::user();
        $tag = Tag::where('user_id', $user->id)->findOrFail($id);
        
        $tag->update([
            'name' => $request->name,
            'description' => $request->description,
            'color' => $request->color ?? $tag->color,
        ]);
        
        return response()->json([
            'message' => 'Tag updated successfully',
            'tag' => $tag
        ]);
    }
    
    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $user = Auth::user();
        $tag = Tag::where('user_id', $user->id)->findOrFail($id);
        
        // Check if tag is being used
        if ($tag->articles()->count() > 0) {
            return response()->json([
                'message' => 'Cannot delete tag that is being used by articles'
            ], 422);
        }
        
        $tag->delete();
        
        return response()->json([
            'message' => 'Tag deleted successfully'
        ]);
    }
    
    /**
     * Bulk tag articles
     */
    public function bulkTag(Request $request)
    {
        $request->validate([
            'tag_id' => 'required|exists:tags,id',
            'article_ids' => 'required|array',
            'article_ids.*' => 'exists:articles,id'
        ]);
        
        $user = Auth::user();
        $tag = Tag::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->findOrFail($request->tag_id);
        
        // Verify all articles belong to user
        $articles = \App\Models\Article::where('user_id', $user->id)
            ->whereIn('id', $request->article_ids)
            ->get();
            
        if ($articles->count() !== count($request->article_ids)) {
            return response()->json([
                'message' => 'Some articles do not belong to you'
            ], 403);
        }
        
        // Attach tag to articles
        $tag->articles()->syncWithoutDetaching($request->article_ids);
        
        // Update usage count
        $tag->increment('usage_count', count($request->article_ids));
        
        return response()->json([
            'message' => 'Articles tagged successfully',
            'tagged_count' => count($request->article_ids)
        ]);
    }
    
    /**
     * Remove tag from articles
     */
    public function removeFromArticles(Request $request, string $tagId)
    {
        $request->validate([
            'article_ids' => 'required|array',
            'article_ids.*' => 'exists:articles,id'
        ]);
        
        $user = Auth::user();
        $tag = Tag::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhereNull('user_id');
            })
            ->findOrFail($tagId);
        
        // Detach tag from articles
        $tag->articles()->detach($request->article_ids);
        
        // Update usage count
        $tag->decrement('usage_count', count($request->article_ids));
        
        return response()->json([
            'message' => 'Tag removed from articles successfully'
        ]);
    }
    
    /**
     * Get tag statistics
     */
    private function getTagStatistics($userId)
    {
        return [
            'total_user_tags' => Tag::forUser($userId)->count(),
            'most_used_tag' => Tag::forUser($userId)->orderBy('usage_count', 'desc')->first(),
            'auto_generated_tags' => Tag::autoGenerated($userId)->count(),
            'articles_with_tags' => \App\Models\Article::where('user_id', $userId)
                ->whereHas('tags')
                ->count(),
            'total_tag_usage' => Tag::forUser($userId)->sum('usage_count')
        ];
    }
    
    /**
     * Get related tags based on article overlap
     */
    private function getRelatedTags($tag, $userId)
    {
        $articleIds = $tag->articles()->where('user_id', $userId)->pluck('articles.id');
        
        return Tag::whereHas('articles', function ($query) use ($articleIds, $userId) {
                $query->whereIn('articles.id', $articleIds)
                      ->where('articles.user_id', $userId);
            })
            ->where('id', '!=', $tag->id)
            ->orderBy('usage_count', 'desc')
            ->limit(5)
            ->get(['id', 'name', 'slug', 'color', 'usage_count']);
    }
    
    /**
     * Generate random color
     */
    private function generateRandomColor()
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9'
        ];
        
        return $colors[array_rand($colors)];
    }
}

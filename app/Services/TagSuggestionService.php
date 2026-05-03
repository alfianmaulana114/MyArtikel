<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Article;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class TagSuggestionService
{
    protected array $stopWords = [
        'the', 'a', 'an', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by',
        'is', 'are', 'was', 'were', 'be', 'been', 'being', 'have', 'has', 'had', 'do', 'does', 'did',
        'will', 'would', 'could', 'should', 'may', 'might', 'must', 'can', 'this', 'that', 'these', 'those'
    ];
    
    /**
     * Suggest tags based on content analysis
     */
    public function suggestTags(string $content, int $userId, ?int $articleId = null): Collection
    {
        $suggestions = collect();
        
        // Get existing user tags for comparison
        $userTags = Tag::forUser($userId)->get();
        
        // Extract keywords from content
        $keywords = $this->extractKeywords($content);
        
        // Score existing tags based on keyword matches
        foreach ($userTags as $tag) {
            $score = $this->calculateTagScore($tag, $keywords, $content);
            if ($score > 0) {
                $suggestions->push([
                    'tag' => $tag,
                    'score' => $score,
                    'reason' => 'existing_tag_match'
                ]);
            }
        }
        
        // Generate new tag suggestions from content
        $newTagSuggestions = $this->generateNewTagSuggestions($keywords, $userId);
        foreach ($newTagSuggestions as $suggestion) {
            $suggestions->push([
                'tag' => $suggestion,
                'score' => $suggestion['score'],
                'reason' => 'content_based'
            ]);
        }
        
        // Get suggestions based on similar articles
        if ($articleId) {
            $similarArticleTags = $this->getTagsFromSimilarArticles($articleId, $userId);
            foreach ($similarArticleTags as $tag) {
                $suggestions->push([
                    'tag' => $tag,
                    'score' => 0.7, // Base score for similar article tags
                    'reason' => 'similar_articles'
                ]);
            }
        }
        
        // Sort by score and limit results
        return $suggestions
            ->sortByDesc('score')
            ->take(10)
            ->values();
    }
    
    /**
     * Extract keywords from content
     */
    private function extractKeywords(string $content): array
    {
        // Remove HTML tags and normalize text
        $text = strip_tags($content);
        $text = strtolower($text);
        
        // Remove punctuation and special characters
        $text = preg_replace('/[^a-z0-9\s]/', ' ', $text);
        
        // Split into words
        $words = explode(' ', $text);
        
        // Filter out stop words and short words
        $keywords = array_filter($words, function ($word) {
            return strlen($word) > 2 && !in_array($word, $this->stopWords);
        });
        
        // Count word frequency
        $keywordCounts = array_count_values($keywords);
        
        // Sort by frequency and return top keywords
        arsort($keywordCounts);
        
        return array_slice($keywordCounts, 0, 50, true);
    }
    
    /**
     * Calculate score for existing tag based on keyword matches
     */
    private function calculateTagScore(Tag $tag, array $keywords, string $content): float
    {
        $score = 0.0;
        $tagName = strtolower($tag->name);
        
        // Exact match bonus
        if (isset($keywords[$tagName])) {
            $score += $keywords[$tagName] * 2.0;
        }
        
        // Partial match
        foreach ($keywords as $keyword => $count) {
            if (strpos($keyword, $tagName) !== false || strpos($tagName, $keyword) !== false) {
                $score += $count * 0.5;
            }
        }
        
        // Description match
        if ($tag->description) {
            $tagDescription = strtolower($tag->description);
            foreach ($keywords as $keyword => $count) {
                if (strpos($tagDescription, $keyword) !== false) {
                    $score += $count * 0.3;
                }
            }
        }
        
        // Usage frequency bonus (popularity)
        $score += ($tag->usage_count * 0.1);
        
        return min($score, 10.0); // Cap at 10
    }
    
    /**
     * Generate new tag suggestions from keywords
     */
    private function generateNewTagSuggestions(array $keywords, int $userId): array
    {
        $suggestions = [];
        
        // Get top keywords as potential tags
        $topKeywords = array_slice($keywords, 0, 15, true);
        
        foreach ($topKeywords as $keyword => $count) {
            // Skip if already exists as tag
            if (Tag::where('user_id', $userId)->where('name', $keyword)->exists()) {
                continue;
            }
            
            // Skip if too generic
            if ($this->isTooGeneric($keyword)) {
                continue;
            }
            
            $suggestions[] = [
                'name' => ucfirst($keyword),
                'slug' => \Illuminate\Support\Str::slug($keyword),
                'description' => 'Auto-generated from content',
                'color' => $this->generateRandomColor(),
                'score' => min($count * 0.8, 8.0),
                'is_auto_generated' => true
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Get tags from similar articles
     */
    private function getTagsFromSimilarArticles(int $articleId, int $userId): Collection
    {
        $currentArticle = Article::find($articleId);
        
        if (!$currentArticle) {
            return collect();
        }
        
        // Get articles with similar content (simple implementation)
        $similarArticles = Article::where('user_id', $userId)
            ->where('id', '!=', $articleId)
            ->where(function ($query) use ($currentArticle) {
                // Simple similarity based on title words
                $titleWords = explode(' ', strtolower($currentArticle->title));
                foreach ($titleWords as $word) {
                    if (strlen($word) > 3) {
                        $query->orWhere('title', 'like', "%{$word}%");
                    }
                }
            })
            ->limit(5)
            ->get();
        
        // Get tags from similar articles
        $tagIds = $similarArticles->flatMap(function ($article) {
            return $article->tags->pluck('id');
        })->unique();
        
        return Tag::whereIn('id', $tagIds)->get();
    }
    
    /**
     * Check if keyword is too generic
     */
    private function isTooGeneric(string $keyword): bool
    {
        $genericWords = [
            'article', 'content', 'text', 'page', 'website', 'web', 'site',
            'information', 'data', 'news', 'story', 'post', 'blog'
        ];
        
        return in_array($keyword, $genericWords);
    }
    
    /**
     * Generate random color
     */
    private function generateRandomColor(): string
    {
        $colors = [
            '#FF6B6B', '#4ECDC4', '#45B7D1', '#96CEB4', '#FFEAA7',
            '#DDA0DD', '#98D8C8', '#F7DC6F', '#BB8FCE', '#85C1E9',
            '#F8C471', '#82E0AA', '#F1948A', '#85C1E9', '#D7BDE2'
        ];
        
        return $colors[array_rand($colors)];
    }
}
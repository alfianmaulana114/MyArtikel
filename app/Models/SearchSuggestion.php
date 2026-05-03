<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchSuggestion extends Model
{
    protected $fillable = [
        'suggestion',
        'type',
        'popularity',
        'metadata',
    ];
    
    protected $casts = [
        'popularity' => 'integer',
        'metadata' => 'array',
    ];
    
    /**
     * Scope for popular suggestions
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('popularity')->limit($limit);
    }
    
    /**
     * Scope for query type suggestions
     */
    public function scopeQueries($query)
    {
        return $query->where('type', 'query');
    }
    
    /**
     * Scope for tag type suggestions
     */
    public function scopeTags($query)
    {
        return $query->where('type', 'tag');
    }
    
    /**
     * Scope for content type suggestions
     */
    public function scopeContent($query)
    {
        return $query->where('type', 'content');
    }
    
    /**
     * Increment popularity
     */
    public function incrementPopularity(int $amount = 1): void
    {
        $this->increment('popularity', $amount);
    }
}
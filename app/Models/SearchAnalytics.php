<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SearchAnalytics extends Model
{
    protected $fillable = [
        'query_hash',
        'query',
        'keywords',
        'search_count',
        'click_count',
        'avg_click_position',
        'related_queries',
    ];
    
    protected $casts = [
        'keywords' => 'array',
        'related_queries' => 'array',
        'avg_click_position' => 'float',
        'search_count' => 'integer',
        'click_count' => 'integer',
    ];
    
    /**
     * Get click-through rate
     */
    public function getClickThroughRateAttribute(): float
    {
        return $this->search_count > 0 ? ($this->click_count / $this->search_count) * 100 : 0;
    }
    
    /**
     * Scope for popular queries
     */
    public function scopePopular($query, int $limit = 10)
    {
        return $query->orderByDesc('search_count')->limit($limit);
    }
    
    /**
     * Scope for high-performing queries
     */
    public function scopeHighPerforming($query, float $minCtr = 50.0)
    {
        return $query->whereRaw('(click_count / search_count) * 100 >= ?', [$minCtr]);
    }
    
    /**
     * Scope for underperforming queries
     */
    public function scopeUnderperforming($query, float $maxCtr = 10.0)
    {
        return $query->whereRaw('(click_count / search_count) * 100 <= ?', [$maxCtr]);
    }
}
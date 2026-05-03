<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SearchHistory extends Model
{
    protected $fillable = [
        'user_id',
        'query',
        'filters',
        'results_count',
        'clicked_result',
        'session_id',
        'ip_address',
        'user_agent',
    ];
    
    protected $casts = [
        'filters' => 'array',
        'clicked_result' => 'boolean',
        'results_count' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Scope for recent searches
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }
    
    /**
     * Scope for successful searches (with results)
     */
    public function scopeSuccessful($query)
    {
        return $query->where('results_count', '>', 0);
    }
    
    /**
     * Scope for clicked searches
     */
    public function scopeClicked($query)
    {
        return $query->where('clicked_result', true);
    }
}
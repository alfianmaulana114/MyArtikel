<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class SummaryQuotaTracking extends Model
{
    protected $fillable = [
        'user_id',
        'service',
        'requests_count',
        'tokens_used',
        'quota_date',
        'metadata',
    ];
    
    protected $casts = [
        'requests_count' => 'integer',
        'tokens_used' => 'integer',
        'quota_date' => 'date',
        'metadata' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Increment request count
     */
    public function incrementRequests(): void
    {
        $this->increment('requests_count');
    }
    
    /**
     * Add tokens to usage
     */
    public function addTokens(int $tokens): void
    {
        $this->increment('tokens_used', $tokens);
    }
    
    /**
     * Check if quota is exceeded
     */
    public function isExceeded(int $maxRequests, int $maxTokens = null): bool
    {
        if ($this->requests_count >= $maxRequests) {
            return true;
        }
        
        if ($maxTokens !== null && $this->tokens_used >= $maxTokens) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Get usage percentage
     */
    public function getUsagePercentage(int $maxRequests): float
    {
        if ($maxRequests <= 0) {
            return 0;
        }
        
        return min(100, ($this->requests_count / $maxRequests) * 100);
    }
    
    /**
     * Scope for today's records
     */
    public function scopeToday($query)
    {
        return $query->where('quota_date', Carbon::today());
    }
    
    /**
     * Scope for specific service
     */
    public function scopeService($query, string $service)
    {
        return $query->where('service', $service);
    }
    
    /**
     * Scope for specific user
     */
    public function scopeUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Get formatted quota date
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->quota_date->format('Y-m-d');
    }
    
    /**
     * Get human-readable service name
     */
    public function getServiceNameAttribute(): string
    {
        return match($this->service) {
            'gemini' => 'Gemini AI',
            'local' => 'Local Algorithm',
            default => ucfirst($this->service)
        };
    }
}
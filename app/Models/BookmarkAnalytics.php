<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookmarkAnalytics extends Model
{
    use HasFactory;

    protected $table = 'bookmark_analytics';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'bookmark_id',
        'action',
        'metadata',
        'device_id',
        'session_id',
        'ip_address',
        'user_agent',
        'occurred_at'
    ];

    protected $casts = [
        'metadata' => 'array',
        'occurred_at' => 'datetime'
    ];

    /**
     * Get the user that owns the analytics.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookmark that owns the analytics.
     */
    public function bookmark(): BelongsTo
    {
        return $this->belongsTo(Bookmark::class);
    }

    /**
     * Scope to get analytics for a specific action.
     */
    public function scopeAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Scope to get analytics for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get analytics for a specific bookmark.
     */
    public function scopeForBookmark($query, $bookmarkId)
    {
        return $query->where('bookmark_id', $bookmarkId);
    }

    /**
     * Scope to get analytics within a date range.
     */
    public function scopeWithinDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('occurred_at', [$startDate, $endDate]);
    }

    /**
     * Scope to get analytics for a specific device.
     */
    public function scopeForDevice($query, $deviceId)
    {
        return $query->where('device_id', $deviceId);
    }

    /**
     * Get the formatted metadata.
     */
    public function getFormattedMetadataAttribute(): array
    {
        return $this->metadata ?? [];
    }

    /**
     * Get the time since the action occurred.
     */
    public function getTimeSinceOccurredAttribute(): string
    {
        return $this->occurred_at->diffForHumans();
    }
}

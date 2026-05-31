<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Bookmark extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'article_id',
        'category_id',
        'notes',
        'tags',
        'priority',
        'is_favorite',
        'is_archived',
        'reminder_at',
        'read_at',
        'read_count',
        'position',
        'source_device',
        'source_browser',
        'created_ip',
    ];

    protected $casts = [
        'tags' => 'array',
        'is_favorite' => 'boolean',
        'is_archived' => 'boolean',
        'priority' => 'integer',
        'read_count' => 'integer',
        'position' => 'integer',
        'reminder_at' => 'datetime',
        'read_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the user that owns the bookmark.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the article that is bookmarked.
     */
    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    /**
     * Get the category of the bookmark.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(BookmarkCategory::class, 'category_id');
    }

    /**
     * Get the analytics for the bookmark.
     */
    public function analytics(): HasMany
    {
        return $this->hasMany(BookmarkAnalytics::class);
    }

    /**
     * Scope to get only favorite bookmarks.
     */
    public function scopeFavorites($query)
    {
        return $query->where('is_favorite', true);
    }

    /**
     * Scope to get only archived bookmarks.
     */
    public function scopeArchived($query)
    {
        return $query->where('is_archived', true);
    }

    /**
     * Scope to get only active (non-archived) bookmarks.
     */
    public function scopeActive($query)
    {
        return $query->where('is_archived', false);
    }

    /**
     * Scope to get only unread bookmarks.
     */
    public function scopeUnread($query)
    {
        return $query->whereNull('read_at');
    }

    /**
     * Scope to get only read bookmarks.
     */
    public function scopeRead($query)
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * Scope to get bookmarks with reminders.
     */
    public function scopeWithReminders($query)
    {
        return $query->whereNotNull('reminder_at');
    }

    /**
     * Scope to get due reminders.
     */
    public function scopeDueReminders($query)
    {
        return $query->whereNotNull('reminder_at')
            ->where('reminder_at', '<=', now());
    }

    /**
     * Scope to get bookmarks for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get bookmarks for a specific category.
     */
    public function scopeInCategory($query, $categoryId)
    {
        return $query->where('category_id', $categoryId);
    }

    /**
     * Scope to search bookmarks by notes or tags.
     */
    public function scopeSearch($query, $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('notes', 'like', "%{$search}%")
                ->orWhereJsonContains('tags', $search)
                ->orWhereHas('article', function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('content', 'like', "%{$search}%");
                });
        });
    }

    /**
     * Mark bookmark as read.
     */
    public function markAsRead(): void
    {
        $this->update([
            'read_at' => now(),
            'read_count' => $this->read_count + 1,
        ]);

        // Record analytics
        $this->recordAnalytics('read');
    }

    /**
     * Mark bookmark as unread.
     */
    public function markAsUnread(): void
    {
        $this->update(['read_at' => null]);
    }

    /**
     * Toggle favorite status.
     */
    public function toggleFavorite(): void
    {
        $this->update(['is_favorite' => ! $this->is_favorite]);
    }

    /**
     * Archive the bookmark.
     */
    public function archive(): void
    {
        $this->update(['is_archived' => true]);
    }

    /**
     * Unarchive the bookmark.
     */
    public function unarchive(): void
    {
        $this->update(['is_archived' => false]);
    }

    /**
     * Record analytics for the bookmark.
     */
    public function recordAnalytics(string $action, array $metadata = []): void
    {
        $this->analytics()->create([
            'user_id' => $this->user_id,
            'action' => $action,
            'metadata' => $metadata,
            'device_id' => request()->header('X-Device-ID'),
            'session_id' => session()->getId(),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'occurred_at' => now(),
        ]);
    }

    /**
     * Get formatted tags.
     */
    public function getFormattedTagsAttribute(): array
    {
        return array_map('trim', $this->tags ?? []);
    }

    /**
     * Check if bookmark has reminder.
     */
    public function getHasReminderAttribute(): bool
    {
        return ! is_null($this->reminder_at);
    }

    /**
     * Check if reminder is due.
     */
    public function getIsReminderDueAttribute(): bool
    {
        return $this->has_reminder && $this->reminder_at->isPast();
    }

    /**
     * Get time since bookmark was created.
     */
    public function getTimeSinceCreatedAttribute(): string
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get reading time if available.
     */
    public function getReadingTimeAttribute(): ?int
    {
        return $this->article ? $this->article->reading_time : null;
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BookmarkCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'name',
        'color',
        'description',
        'position',
        'is_public',
        'is_default'
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'is_default' => 'boolean',
        'position' => 'integer'
    ];

    /**
     * Get the user that owns the category.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the bookmarks for the category.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'category_id');
    }

    /**
     * Scope to get only public categories.
     */
    public function scopePublic($query)
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to get only default categories.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope to get categories for a specific user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Get formatted color with hash prefix if needed.
     */
    public function getFormattedColorAttribute(): string
    {
        return str_starts_with($this->color, '#') ? $this->color : '#' . $this->color;
    }

    /**
     * Get bookmark count for the category.
     */
    public function getBookmarksCountAttribute(): int
    {
        return $this->bookmarks()->count();
    }

    /**
     * Get unread bookmarks count for the category.
     */
    public function getUnreadBookmarksCountAttribute(): int
    {
        return $this->bookmarks()->whereNull('read_at')->count();
    }

    /**
     * Get the most recent bookmark in this category.
     */
    public function getLatestBookmarkAttribute()
    {
        return $this->bookmarks()->latest()->first();
    }
}
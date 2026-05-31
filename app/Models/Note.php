<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Note extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'content_json',
        'is_rich_text',
        'user_id',
        'article_id',
        'type',
        'is_private',
        'paragraph_index',
        'paragraph_id',
        'start_offset',
        'end_offset',
        'category',
        'tags',
        'device_id',
        'last_synced_at',
        'sync_status',
        'search_vector',
    ];

    protected $casts = [
        'is_private' => 'boolean',
        'is_rich_text' => 'boolean',
        'content_json' => 'array',
        'tags' => 'array',
        'last_synced_at' => 'datetime',
        'paragraph_index' => 'integer',
        'start_offset' => 'integer',
        'end_offset' => 'integer',
    ];

    protected $dates = [
        'last_synced_at',
    ];

    // Constants for note types
    const TYPE_PERSONAL = 'personal';

    const TYPE_RESEARCH = 'research';

    const TYPE_DRAFT = 'draft';

    // Constants for sync status
    const SYNC_SYNCED = 'synced';

    const SYNC_PENDING = 'pending';

    const SYNC_CONFLICT = 'conflict';

    const SYNC_ERROR = 'error';

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function projects(): BelongsToMany
    {
        return $this->belongsToMany(Project::class, 'project_notes')
            ->withPivot('outline_id')
            ->withTimestamps();
    }

    /**
     * Scope for searching notes
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
                ->orWhere('content', 'like', "%{$search}%")
                ->orWhere('search_vector', 'like', "%{$search}%");
        });
    }

    /**
     * Scope for filtering by category
     */
    public function scopeByCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    /**
     * Scope for filtering by tags
     */
    public function scopeByTags(Builder $query, array $tags): Builder
    {
        return $query->whereJsonContains('tags', $tags);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeByType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Scope for filtering by sync status
     */
    public function scopeBySyncStatus(Builder $query, string $status): Builder
    {
        return $query->where('sync_status', $status);
    }

    /**
     * Scope for notes that need sync
     */
    public function scopeNeedsSync(Builder $query): Builder
    {
        return $query->whereIn('sync_status', [self::SYNC_PENDING, self::SYNC_CONFLICT]);
    }

    /**
     * Check if note is anchored to a paragraph
     */
    public function isAnchored(): bool
    {
        return ! is_null($this->paragraph_index) || ! is_null($this->paragraph_id);
    }

    /**
     * Get the anchor position as array
     */
    public function getAnchorPosition(): ?array
    {
        if (! $this->isAnchored()) {
            return null;
        }

        return [
            'paragraph_index' => $this->paragraph_index,
            'paragraph_id' => $this->paragraph_id,
            'start_offset' => $this->start_offset,
            'end_offset' => $this->end_offset,
        ];
    }

    /**
     * Set search vector for better search performance
     */
    public function setSearchVector(): void
    {
        $this->search_vector = implode(' ', array_filter([
            $this->title,
            $this->content,
            $this->category,
            is_array($this->tags) ? implode(' ', $this->tags) : '',
        ]));
        $this->saveQuietly();
    }

    /**
     * Boot method to set search vector automatically
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($note) {
            if ($note->isDirty(['title', 'content', 'category', 'tags'])) {
                $note->search_vector = implode(' ', array_filter([
                    $note->title,
                    $note->content,
                    $note->category,
                    is_array($note->tags) ? implode(' ', $note->tags) : '',
                ]));
            }
        });
    }
}

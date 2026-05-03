<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Article extends SecureModel
{
    protected $fillable = [
        'title',
        'slug',
        'source_url',
        'canonical_url',
        'source_domain',
        'content',
        'content_sanitized',
        'text_extracted',
        'content_hash',
        'metadata',
        'excerpt',
        'featured_image',
        'user_id',
        'status',
        'processing_status',
        'processing_error',
        'fetched_at',
        'published_at',
        'view_count',
        'is_featured',
    ];
    
    protected $casts = [
        'published_at' => 'datetime',
        'fetched_at' => 'datetime',
        'is_featured' => 'boolean',
        'view_count' => 'integer',
        'metadata' => 'array',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class);
    }
    
    public function notes(): HasMany
    {
        return $this->hasMany(Note::class);
    }
    
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }
    
    public function summaries(): HasMany
    {
        return $this->hasMany(Summary::class);
    }

    /**
     * Secure scope for filtering by status
     */
    public function scopeByStatus($query, string $status)
    {
        $allowedStatuses = ['draft', 'published', 'archived'];
        
        if (!in_array($status, $allowedStatuses)) {
            throw new \InvalidArgumentException('Invalid status: ' . $status);
        }

        return $query->where('status', $status);
    }

    /**
     * Secure scope for filtering by slug
     */
    public function scopeBySlug($query, string $slug)
    {
        // Validate slug format
        if (!preg_match('/^[a-z0-9-]+$/', $slug)) {
            throw new \InvalidArgumentException('Invalid slug format');
        }

        return $query->where('slug', $slug);
    }
}

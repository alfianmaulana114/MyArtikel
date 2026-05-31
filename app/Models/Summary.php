<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Summary extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'user_id',
        'content',
        'word_count',
        'type',
        'key_points',
        'source',
        'status',
        'cache_key',
        'processing_started_at',
        'processing_completed_at',
        'processing_time_ms',
        'error_message',
    ];

    protected $casts = [
        'word_count' => 'integer',
        'key_points' => 'array',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'processing_time_ms' => 'integer',
    ];

    public function article(): BelongsTo
    {
        return $this->belongsTo(Article::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if summary is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if summary is processing
     */
    public function isProcessing(): bool
    {
        return $this->status === 'processing' || $this->status === 'pending';
    }

    /**
     * Check if summary failed
     */
    public function isFailed(): bool
    {
        return $this->status === 'failed';
    }

    /**
     * Get processing time in human readable format
     */
    public function getProcessingTimeHumanAttribute(): string
    {
        if (! $this->processing_time_ms) {
            return 'N/A';
        }

        $seconds = $this->processing_time_ms / 1000;

        if ($seconds < 1) {
            return $this->processing_time_ms.'ms';
        } elseif ($seconds < 60) {
            return round($seconds, 2).'s';
        } else {
            return round($seconds / 60, 2).'min';
        }
    }

    /**
     * Get formatted summary with key points
     */
    public function getFormattedSummaryAttribute(): array
    {
        return [
            'summary' => $this->content,
            'key_points' => $this->key_points ?? [],
            'word_count' => $this->word_count,
            'source' => $this->source,
            'type' => $this->type,
            'processing_time' => $this->processing_time_human,
        ];
    }

    /**
     * Scope for completed summaries
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Scope for processing summaries
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['processing', 'pending']);
    }

    /**
     * Scope for failed summaries
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    /**
     * Scope for AI generated summaries
     */
    public function scopeAiGenerated($query)
    {
        return $query->where('type', 'ai_generated');
    }

    /**
     * Scope for manual summaries
     */
    public function scopeManual($query)
    {
        return $query->where('type', 'manual');
    }
}

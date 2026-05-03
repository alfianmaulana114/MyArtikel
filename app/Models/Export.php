<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Export extends Model
{
    protected $fillable = [
        'user_id',
        'type',
        'file_path',
        'file_size',
        'status',
        'metadata',
        'expires_at',
        'processing_started_at',
        'processing_completed_at',
        'processing_time_ms',
        'error_message',
    ];
    
    protected $casts = [
        'file_size' => 'integer',
        'metadata' => 'array',
        'expires_at' => 'datetime',
        'processing_started_at' => 'datetime',
        'processing_completed_at' => 'datetime',
        'processing_time_ms' => 'integer',
    ];
    
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Check if export is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
    
    /**
     * Check if export is ready for download
     */
    public function isReady(): bool
    {
        return $this->status === 'completed' && !$this->isExpired();
    }
    
    /**
     * Get download URL
     */
    public function getDownloadUrl(): ?string
    {
        if (!$this->isReady()) {
            return null;
        }
        
        return url('storage/exports/' . basename($this->file_path));
    }
    
    /**
     * Get processing time in human readable format
     */
    public function getProcessingTimeHumanAttribute(): string
    {
        if (!$this->processing_time_ms) {
            return 'N/A';
        }
        
        $seconds = $this->processing_time_ms / 1000;
        
        if ($seconds < 1) {
            return $this->processing_time_ms . 'ms';
        } elseif ($seconds < 60) {
            return round($seconds, 2) . 's';
        } else {
            return round($seconds / 60, 2) . 'min';
        }
    }
    
    /**
     * Get file size in human readable format
     */
    public function getFileSizeHumanAttribute(): string
    {
        if (!$this->file_size) {
            return 'N/A';
        }
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $size = $this->file_size;
        $unitIndex = 0;
        
        while ($size >= 1024 && $unitIndex < count($units) - 1) {
            $size /= 1024;
            $unitIndex++;
        }
        
        return round($size, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Scope for active (non-expired) exports
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }
    
    /**
     * Scope for completed exports
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }
    
    /**
     * Scope for processing exports
     */
    public function scopeProcessing($query)
    {
        return $query->whereIn('status', ['processing', 'pending']);
    }
    
    /**
     * Scope for failed exports
     */
    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }
    
    /**
     * Scope for specific user
     */
    public function scopeUser($query, int $userId)
    {
        return $query->where('user_id', $userId);
    }
    
    /**
     * Scope for specific type
     */
    public function scopeType($query, string $type)
    {
        return $query->where('type', $type);
    }
}
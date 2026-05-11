<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',
        'title',
        'description',
        'status',
        'deadline',
        'metadata',
    ];

    protected $casts = [
        'deadline' => 'date',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function outlines(): HasMany
    {
        return $this->hasMany(ProjectOutline::class)->orderBy('position');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'project_articles')
            ->withPivot('role', 'notes')
            ->withTimestamps();
    }

    public function notes(): BelongsToMany
    {
        return $this->belongsToMany(Note::class, 'project_notes')
            ->withPivot('outline_id')
            ->withTimestamps();
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeActive($query)
    {
        return $query->whereIn('status', ['planning', 'drafting', 'reviewing']);
    }

    public function scopeByDeadline($query, $date)
    {
        return $query->where('deadline', '<=', $date);
    }

    public function scopeSearch($query, string $search)
    {
        return $query->where(function ($q) use ($search) {
            $q->where('title', 'like', "%{$search}%")
              ->orWhere('description', 'like', "%{$search}%");
        });
    }

    public function getProgress(): array
    {
        $outlines = $this->outlines()->get();
        $total = $outlines->count();
        
        if ($total === 0) {
            return ['total' => 0, 'completed' => 0, 'percentage' => 0];
        }

        $completed = $outlines->filter(function ($outline) {
            $content = trim($outline->content ?? '');
            return !empty($content) && strlen($content) > 50;
        })->count();

        return [
            'total' => $total,
            'completed' => $completed,
            'percentage' => round(($completed / $total) * 100, 1),
        ];
    }

    public function getStats(): array
    {
        return [
            'articles_count' => $this->articles()->count(),
            'notes_count' => $this->notes()->count(),
            'outlines_count' => $this->outlines()->count(),
            'word_count' => $this->outlines()->sum(function ($outline) {
                return str_word_count($outline->content ?? '');
            }),
            'progress' => $this->getProgress(),
        ];
    }

    public function addArticle(Article $article, string $role = 'reference', ?string $notes = null): void
    {
        $this->articles()->syncWithoutDetaching([
            $article->id => ['role' => $role, 'notes' => $notes]
        ]);
    }

    public function removeArticle(Article $article): void
    {
        $this->articles()->detach($article->id);
    }

    public function getOutlineTree(): array
    {
        $outlines = $this->outlines()->get();
        return $this->buildTree($outlines, null);
    }

    protected function buildTree($outlines, $parentId): array
    {
        $tree = [];
        
        foreach ($outlines as $outline) {
            if ($outline->parent_id === $parentId) {
                $children = $this->buildTree($outlines, $outline->id);
                $tree[] = [
                    'id' => $outline->id,
                    'title' => $outline->title,
                    'section_type' => $outline->section_type,
                    'content' => $outline->content,
                    'position' => $outline->position,
                    'children' => $children,
                ];
            }
        }

        return $tree;
    }
}

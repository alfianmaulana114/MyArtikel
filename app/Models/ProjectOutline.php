<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProjectOutline extends Model
{
    use HasFactory;

    protected $fillable = [
        'project_id',
        'title',
        'section_type',
        'content',
        'position',
        'parent_id',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    const SECTION_TYPES = [
        'title_page' => 'Halaman Judul',
        'abstract' => 'Abstrak',
        'introduction' => 'Pendahuluan',
        'literature_review' => 'Tinjauan Pustaka',
        'methodology' => 'Metodologi',
        'results' => 'Hasil',
        'discussion' => 'Pembahasan',
        'conclusion' => 'Kesimpulan',
        'references' => 'Daftar Pustaka',
        'appendix' => 'Lampiran',
        'custom' => 'Custom',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ProjectOutline::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ProjectOutline::class, 'parent_id')->orderBy('position');
    }

    public function notes(): HasMany
    {
        return $this->hasMany(ProjectNote::class, 'outline_id');
    }

    public function getWordCount(): int
    {
        return str_word_count($this->content ?? '');
    }

    public function getStatus(): string
    {
        $content = trim($this->content ?? '');
        
        if (empty($content)) {
            return 'empty';
        } elseif (strlen($content) < 50) {
            return 'draft';
        } elseif (strlen($content) < 200) {
            return 'in_progress';
        }
        
        return 'completed';
    }

    public function getDescendants(): array
    {
        $descendants = [];
        $children = $this->children()->get();

        foreach ($children as $child) {
            $descendants[] = $child;
            $descendants = array_merge($descendants, $child->getDescendants());
        }

        return $descendants;
    }
}

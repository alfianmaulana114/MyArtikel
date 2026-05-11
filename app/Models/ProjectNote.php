<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProjectNote extends Model
{
    protected $table = 'project_notes';

    protected $fillable = [
        'project_id',
        'note_id',
        'outline_id',
    ];

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }

    public function outline(): BelongsTo
    {
        return $this->belongsTo(ProjectOutline::class, 'outline_id');
    }
}

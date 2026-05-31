<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProjectChatMessage extends Model
{
    protected $fillable = [
        'project_id',
        'user_id',
        'role',
        'content',
        'context_articles',
    ];

    protected $casts = [
        'context_articles' => 'array',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

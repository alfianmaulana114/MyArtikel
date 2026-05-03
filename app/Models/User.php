<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'password', 'is_admin', 'is_active', 'avatar', 'bio'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_admin' => 'boolean',
            'is_active' => 'boolean',
        ];
    }
    
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
    
    public function notes()
    {
        return $this->hasMany(Note::class);
    }
    
    public function bookmarks()
    {
        return $this->hasMany(Bookmark::class);
    }
    
    public function bookmarkCategories()
    {
        return $this->hasMany(BookmarkCategory::class);
    }
    
    public function bookmarkAnalytics()
    {
        return $this->hasMany(BookmarkAnalytics::class);
    }
    
    public function bookmarkSyncDevices()
    {
        return $this->hasMany(BookmarkSync::class);
    }
    
    public function summaries()
    {
        return $this->hasMany(Summary::class);
    }
    
    public function tags()
    {
        return $this->hasMany(Tag::class);
    }
}

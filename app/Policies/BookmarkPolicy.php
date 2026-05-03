<?php

namespace App\Policies;

use App\Models\Bookmark;
use App\Models\User;

class BookmarkPolicy
{
    /**
     * Determine whether the user can view the bookmark.
     */
    public function view(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can create bookmarks.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create bookmarks
    }

    /**
     * Determine whether the user can update the bookmark.
     */
    public function update(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can delete the bookmark.
     */
    public function delete(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can restore the bookmark.
     */
    public function restore(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }

    /**
     * Determine whether the user can permanently delete the bookmark.
     */
    public function forceDelete(User $user, Bookmark $bookmark): bool
    {
        return $user->id === $bookmark->user_id;
    }
}
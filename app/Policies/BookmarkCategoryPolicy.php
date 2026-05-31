<?php

namespace App\Policies;

use App\Models\BookmarkCategory;
use App\Models\User;

class BookmarkCategoryPolicy
{
    /**
     * Determine whether the user can view the category.
     */
    public function view(User $user, BookmarkCategory $category): bool
    {
        return $user->id === $category->user_id || $category->is_public;
    }

    /**
     * Determine whether the user can create categories.
     */
    public function create(User $user): bool
    {
        return true; // Any authenticated user can create categories
    }

    /**
     * Determine whether the user can update the category.
     */
    public function update(User $user, BookmarkCategory $category): bool
    {
        return $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can delete the category.
     */
    public function delete(User $user, BookmarkCategory $category): bool
    {
        return $user->id === $category->user_id && ! $category->is_default;
    }

    /**
     * Determine whether the user can restore the category.
     */
    public function restore(User $user, BookmarkCategory $category): bool
    {
        return $user->id === $category->user_id;
    }

    /**
     * Determine whether the user can permanently delete the category.
     */
    public function forceDelete(User $user, BookmarkCategory $category): bool
    {
        return $user->id === $category->user_id && ! $category->is_default;
    }
}

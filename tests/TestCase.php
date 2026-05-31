<?php

namespace Tests;

use App\Models\Article;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use App\Models\Note;
use App\Models\Project;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function createUser(array $attributes = []): User
    {
        return User::factory()->create($attributes);
    }

    protected function createAdmin(): User
    {
        return User::factory()->create([
            'is_admin' => true,
            'is_active' => true,
        ]);
    }

    protected function actingAsUser(?User $user = null): self
    {
        return $this->actingAs($user ?? $this->createUser());
    }

    protected function actingAsAdmin(): self
    {
        return $this->actingAs($this->createAdmin());
    }

    protected function createArticle(?User $user = null, array $attributes = []): Article
    {
        return Article::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
        ], $attributes));
    }

    protected function createReadyArticle(?User $user = null): Article
    {
        return Article::factory()->ready()->create([
            'user_id' => ($user ?? $this->createUser())->id,
        ]);
    }

    protected function createTag(?User $user = null, array $attributes = []): Tag
    {
        return Tag::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
        ], $attributes));
    }

    protected function createNote(?User $user = null, ?Article $article = null, array $attributes = []): Note
    {
        return Note::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
            'article_id' => ($article ?? $this->createArticle($user))->id,
        ], $attributes));
    }

    protected function createBookmark(?User $user = null, ?Article $article = null, array $attributes = []): Bookmark
    {
        return Bookmark::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
            'article_id' => ($article ?? $this->createArticle($user))->id,
        ], $attributes));
    }

    protected function createBookmarkCategory(?User $user = null, array $attributes = []): BookmarkCategory
    {
        return BookmarkCategory::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
        ], $attributes));
    }

    protected function createProject(?User $user = null, array $attributes = []): Project
    {
        return Project::factory()->create(array_merge([
            'user_id' => ($user ?? $this->createUser())->id,
        ], $attributes));
    }

    protected function assertJsonOk($response): void
    {
        $response->assertStatus(200);
        $this->assertTrue($response->json('success') ?? true);
    }

    protected function assertJsonError($response, int $status = 400): void
    {
        $response->assertStatus($status);
        $this->assertFalse($response->json('success') ?? false);
    }
}

<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use App\Models\Bookmark;
use App\Models\BookmarkCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BookmarkTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\HtmlSanitizationMiddleware::class);
        $this->user = User::factory()->create();
    }

    public function test_bookmarks_index_page_loads()
    {
        $article = Article::factory()->create(['user_id' => $this->user->id]);
        Bookmark::factory()->create(['user_id' => $this->user->id, 'article_id' => $article->id]);

        $response = $this->actingAs($this->user)->get('/bookmarks');

        $response->assertStatus(200);
    }

    public function test_create_bookmark()
    {
        $article = Article::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)
            ->postJson('/bookmarks', [
                'article_id' => $article->id,
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('bookmarks', [
            'user_id' => $this->user->id,
            'article_id' => $article->id,
        ]);
    }

    public function test_bookmark_categories_list()
    {
        BookmarkCategory::factory()->count(2)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/bookmarks');

        $response->assertStatus(200);
    }

    public function test_bookmarks_require_authentication()
    {
        $response = $this->get('/bookmarks');
        $this->assertNotEquals(200, $response->status());
    }
}

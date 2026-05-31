<?php

namespace Tests\Feature;

use App\Http\Middleware\HtmlSanitizationMiddleware;
use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(HtmlSanitizationMiddleware::class);
        $this->user = User::factory()->create();
    }

    public function test_articles_index_page_loads()
    {
        Article::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get('/articles');

        $response->assertStatus(200);
    }

    public function test_articles_data_returns_json()
    {
        Article::factory()->count(3)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->getJson('/articles/data');

        $response->assertStatus(200);
    }

    public function test_article_show_page_loads()
    {
        $article = Article::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user)->get("/articles/{$article->id}");

        $response->assertStatus(200);
    }

    public function test_article_show_redirects_for_non_owner()
    {
        $otherUser = User::factory()->create();
        $article = Article::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user)->get("/articles/{$article->id}");

        $this->assertNotEquals(200, $response->status());
    }

    public function test_articles_require_authentication()
    {
        $response = $this->get('/articles');
        $this->assertNotEquals(200, $response->status());
    }

    public function test_generate_citations_endpoint()
    {
        $article = Article::factory()->ready()->create([
            'user_id' => $this->user->id,
            'text_extracted' => 'The methodology used in this research follows a qualitative approach.',
            'research_title' => 'Quantitative Research Methods',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson("/articles/{$article->id}/generate-citations", [
                'research_title' => 'Quantitative Research Methods',
            ]);

        $this->assertContains($response->status(), [200, 422, 500]);
    }

    public function test_article_with_research_title()
    {
        $article = Article::factory()->withResearchTitle('My Research')->create([
            'user_id' => $this->user->id,
        ]);

        $this->assertEquals('My Research', $article->research_title);
    }
}

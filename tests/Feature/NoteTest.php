<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use App\Models\Note;
use Illuminate\Foundation\Testing\RefreshDatabase;

class NoteTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutMiddleware(\App\Http\Middleware\HtmlSanitizationMiddleware::class);
        $this->user = User::factory()->create();
        $this->article = Article::factory()->create(['user_id' => $this->user->id]);
    }

    public function test_notes_index_page_loads()
    {
        Note::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
        ]);

        $response = $this->actingAs($this->user)->get('/notes');

        $response->assertStatus(200);
    }

    public function test_notes_data_returns_json()
    {
        Note::factory()->count(3)->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
        ]);

        $response = $this->actingAs($this->user)->getJson('/notes/data');

        $response->assertStatus(200);
    }

    public function test_create_note()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/notes', [
                'title' => 'My Note',
                'content' => 'This is a test note.',
                'article_id' => $this->article->id,
            ]);

        $response->assertStatus(201)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notes', [
            'title' => 'My Note',
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
        ]);
    }

    public function test_update_note()
    {
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
            'title' => 'Original Title',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/notes/{$note->id}", [
                'title' => 'Updated Title',
                'content' => 'Updated content.',
                'article_id' => $this->article->id,
            ]);

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('notes', [
            'id' => $note->id,
            'title' => 'Updated Title',
        ]);
    }

    public function test_delete_note()
    {
        $note = Note::factory()->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/notes/{$note->id}");

        $response->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseMissing('notes', ['id' => $note->id]);
    }

    public function test_search_notes()
    {
        Note::factory()->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
            'content' => 'This is about Laravel development.',
        ]);
        Note::factory()->create([
            'user_id' => $this->user->id,
            'article_id' => $this->article->id,
            'content' => 'This is about Vue.js frontend.',
        ]);

        $response = $this->actingAs($this->user)
            ->postJson('/notes/search', ['query' => 'Laravel']);

        $response->assertStatus(200);
    }

    public function test_note_requires_authentication()
    {
        $response = $this->get('/notes');
        $this->assertNotEquals(200, $response->status());
    }
}

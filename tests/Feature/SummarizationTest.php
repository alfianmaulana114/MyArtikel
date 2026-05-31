<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\Summary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SummarizationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Article $article;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->article = Article::factory()->create([
            'user_id' => $this->user->id,
            'title' => 'Test Article for Summarization',
            'text_extracted' => 'This is a test article with multiple sentences. It contains important information that should be summarized. The article discusses various topics and provides detailed explanations. This content will be used to test the summarization functionality. We need to ensure that the system can properly extract key points and create a concise summary.',
            'processing_status' => 'ready',
        ]);
    }

    public function test_local_summary_generation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'max_words' => 100,
                'prefer_ai' => false,
                'language' => 'en',
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'content',
                    'word_count',
                    'key_points',
                    'status',
                ],
                'message',
            ]);
        $this->assertDatabaseHas('summaries', [
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
    }

    public function test_quota_management()
    {
        $articles = Article::factory()->count(5)->create([
            'user_id' => $this->user->id,
            'text_extracted' => 'Test content for quota management.',
            'processing_status' => 'ready',
        ]);

        foreach ($articles as $article) {
            $response = $this->actingAs($this->user)
                ->postJson('/summaries/generate', [
                    'article_id' => $article->id,
                    'prefer_ai' => false,
                ]);

            $response->assertStatus(200);
        }

        $response = $this->actingAs($this->user)
            ->getJson('/summaries/quota');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quota_status' => [
                        'gemini',
                        'local',
                    ],
                ],
            ]);

        $this->assertEquals(5, $response->json('data.quota_status.local.used_today'));
    }

    public function test_async_summary_generation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'async' => true,
                'prefer_ai' => false,
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary_id',
                    'status',
                    'message',
                ],
            ]);

        $summaryId = $response->json('data.summary_id');

        $statusResponse = $this->actingAs($this->user)
            ->getJson("/summaries/{$summaryId}/status");

        $statusResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                ],
            ]);
    }

    public function test_summary_regeneration()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false,
            ]);

        $response->assertStatus(200);

        $regenerateResponse = $this->actingAs($this->user)
            ->postJson("/summaries/{$this->article->id}/regenerate", [
                'prefer_ai' => false,
            ]);

        $regenerateResponse->assertStatus(200);
        $newSummary = $regenerateResponse->json('data.content');

        $this->assertNotEmpty($newSummary);
        $this->assertDatabaseHas('summaries', [
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);
    }

    public function test_manual_summary_update()
    {
        $summary = Summary::factory()->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'type' => 'manual',
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/summaries/{$summary->id}", [
                'content' => 'This is a manually updated summary.',
                'key_points' => ['Point 1', 'Point 2'],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Summary updated successfully',
            ]);

        $this->assertDatabaseHas('summaries', [
            'id' => $summary->id,
            'content' => 'This is a manually updated summary.',
            'word_count' => 6,
        ]);
    }

    public function test_summary_deletion()
    {
        $summary = Summary::factory()->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/summaries/{$summary->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Summary deleted successfully',
            ]);

        $this->assertDatabaseMissing('summaries', ['id' => $summary->id]);
    }

    public function test_quota_exceeded_handling()
    {
        config(['services.quota.local_daily_limit' => 1]);

        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false,
            ]);

        $response->assertStatus(200);

        $article2 = Article::factory()->create([
            'user_id' => $this->user->id,
            'text_extracted' => 'Different article for quota test.',
            'processing_status' => 'ready',
        ]);

        $quotaResponse = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $article2->id,
                'prefer_ai' => false,
            ]);

        $quotaResponse->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'Daily quota exceeded',
            ]);
    }

    public function test_summary_listing()
    {
        Summary::factory()->count(5)->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => 'completed',
        ]);

        $response = $this->actingAs($this->user)
            ->getJson('/summaries/data');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'current_page',
                    'data' => [
                        '*' => [
                            'id',
                            'content',
                            'word_count',
                            'source',
                            'status',
                            'created_at',
                        ],
                    ],
                ],
            ]);

        $this->assertCount(5, $response->json('data.data'));
    }

    public function test_summary_caching()
    {
        $response1 = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false,
            ]);

        $response1->assertStatus(200);
        $summary1 = $response1->json('data');

        $response2 = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false,
            ]);

        $response2->assertStatus(200);
        $summary2 = $response2->json('data');

        $this->assertEquals($summary1['content'], $summary2['content']);
    }
}

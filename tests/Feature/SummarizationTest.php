<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Article;
use App\Models\Summary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

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
            'content' => 'This is a test article with multiple sentences. It contains important information that should be summarized. The article discusses various topics and provides detailed explanations. This content will be used to test the summarization functionality. We need to ensure that the system can properly extract key points and create a concise summary.'
        ]);
    }

    /**
     * Test summary generation with local service
     */
    public function test_local_summary_generation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'max_words' => 100,
                'prefer_ai' => false,
                'language' => 'en'
            ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'content',
                    'word_count',
                    'key_points',
                    'source',
                    'status'
                ],
                'source',
                'message'
            ]);

        $this->assertEquals('local', $response->json('source'));
        $this->assertDatabaseHas('summaries', [
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'source' => 'local',
            'status' => 'completed'
        ]);
    }

    /**
     * Test quota management
     */
    public function test_quota_management()
    {
        // Generate multiple summaries to test quota
        for ($i = 0; $i < 5; $i++) {
            $response = $this->actingAs($this->user)
                ->postJson('/summaries/generate', [
                    'article_id' => $this->article->id,
                    'prefer_ai' => false
                ]);
            
            $response->assertStatus(200);
        }

        // Check quota status
        $response = $this->actingAs($this->user)
            ->getJson('/summaries/quota');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'quota_status' => [
                        'gemini',
                        'local'
                    ]
                ]
            ]);

        $this->assertEquals(5, $response->json('data.quota_status.local.used_today'));
    }

    /**
     * Test async summary generation
     */
    public function test_async_summary_generation()
    {
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'async' => true,
                'prefer_ai' => false
            ]);

        $response->assertStatus(202)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'summary_id',
                    'status',
                    'message'
                ]
            ]);

        $summaryId = $response->json('data.summary_id');
        
        // Check status
        $statusResponse = $this->actingAs($this->user)
            ->getJson("/summaries/{$summaryId}/status");

        $statusResponse->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'id',
                    'status',
                    'source',
                    'processing_time_ms',
                    'error_message',
                    'created_at',
                    'updated_at'
                ]
            ]);
    }

    /**
     * Test summary regeneration
     */
    public function test_summary_regeneration()
    {
        // First generate a summary
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false
            ]);

        $response->assertStatus(200);
        $originalSummary = $response->json('data.content');

        // Regenerate
        $regenerateResponse = $this->actingAs($this->user)
            ->postJson("/summaries/{$this->article->id}/regenerate", [
                'prefer_ai' => false
            ]);

        $regenerateResponse->assertStatus(200);
        $newSummary = $regenerateResponse->json('data.content');

        // Summaries might be different due to algorithm variations
        $this->assertNotEmpty($newSummary);
        $this->assertDatabaseHas('summaries', [
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
        ]);
    }

    /**
     * Test manual summary update
     */
    public function test_manual_summary_update()
    {
        $summary = Summary::factory()->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'type' => 'manual',
            'status' => 'completed'
        ]);

        $response = $this->actingAs($this->user)
            ->putJson("/summaries/{$summary->id}", [
                'content' => 'This is a manually updated summary.',
                'key_points' => ['Point 1', 'Point 2']
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Summary updated successfully'
            ]);

        $this->assertDatabaseHas('summaries', [
            'id' => $summary->id,
            'content' => 'This is a manually updated summary.',
            'word_count' => 6
        ]);
    }

    /**
     * Test summary deletion
     */
    public function test_summary_deletion()
    {
        $summary = Summary::factory()->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id
        ]);

        $response = $this->actingAs($this->user)
            ->deleteJson("/summaries/{$summary->id}");

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Summary deleted successfully'
            ]);

        $this->assertDatabaseMissing('summaries', ['id' => $summary->id]);
    }

    /**
     * Test quota exceeded handling
     */
    public function test_quota_exceeded_handling()
    {
        // Set very low quota for testing
        config(['services.quota.local_daily_limit' => 1]);

        // First request should succeed
        $response = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false
            ]);

        $response->assertStatus(200);

        // Second request should fail due to quota
        $quotaResponse = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false
            ]);

        $quotaResponse->assertStatus(429)
            ->assertJson([
                'success' => false,
                'error' => 'Daily quota exceeded'
            ]);
    }

    /**
     * Test summary listing
     */
    public function test_summary_listing()
    {
        Summary::factory()->count(5)->create([
            'article_id' => $this->article->id,
            'user_id' => $this->user->id,
            'status' => 'completed'
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
                            'created_at'
                        ]
                    ]
                ]
            ]);

        $this->assertCount(5, $response->json('data.data'));
    }

    /**
     * Test summary caching
     */
    public function test_summary_caching()
    {
        // First request
        $response1 = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false
            ]);

        $response1->assertStatus(200);
        $summary1 = $response1->json('data');

        // Second request with same parameters should use cache
        $response2 = $this->actingAs($this->user)
            ->postJson('/summaries/generate', [
                'article_id' => $this->article->id,
                'prefer_ai' => false
            ]);

        $response2->assertStatus(200);
        $summary2 = $response2->json('data');

        // Should return the same cached summary
        $this->assertEquals($summary1['content'], $summary2['content']);
        $this->assertEquals('cache', $response2->json('source'));
    }
}

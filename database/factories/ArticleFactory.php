<?php

namespace Database\Factories;

use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Article>
 */
class ArticleFactory extends Factory
{
    protected $model = Article::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'slug' => fake()->unique()->slug(),
            'content' => fake()->paragraphs(5, true),
            'content_sanitized' => fake()->paragraphs(5, true),
            'text_extracted' => fake()->paragraphs(5, true),
            'content_hash' => md5(fake()->text()),
            'user_id' => User::factory(),
            'status' => 'published',
            'processing_status' => 'ready',
            'source_url' => fake()->url(),
            'source_domain' => fake()->domainName(),
            'source_type' => 'url',
            'metadata' => [],
        ];
    }

    public function ready(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'ready',
            'processing_error' => null,
        ]);
    }

    public function processing(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'extracting',
        ]);
    }

    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'failed',
            'processing_error' => 'Simulated failure for testing',
        ]);
    }

    public function queued(): static
    {
        return $this->state(fn (array $attributes) => [
            'processing_status' => 'queued',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }

    public function withResearchTitle(string $title = 'Test Research Topic'): static
    {
        return $this->state(fn (array $attributes) => [
            'research_title' => $title,
        ]);
    }

    public function withCitations(): static
    {
        return $this->state(fn (array $attributes) => [
            'ai_quotation_suggestions' => [
                ['quote' => 'This is a test citation from the article text content.', 'relevance' => 'Relevant to research topic', 'position' => 'pembahasan'],
                ['quote' => 'Another important quote for citation purposes.', 'relevance' => 'Supports methodology claims', 'position' => 'metodologi'],
            ],
        ]);
    }
}

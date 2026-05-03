<?php

namespace Database\Factories;

use App\Models\Summary;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Summary>
 */
class SummaryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $sources = ['gemini', 'local', 'manual'];
        $statuses = ['pending', 'processing', 'completed', 'failed'];
        $types = ['ai_generated', 'manual'];
        
        return [
            'article_id' => Article::factory(),
            'user_id' => User::factory(),
            'content' => $this->faker->paragraphs(3, true),
            'word_count' => $this->faker->numberBetween(50, 300),
            'type' => $this->faker->randomElement($types),
            'source' => $this->faker->randomElement($sources),
            'status' => $this->faker->randomElement($statuses),
            'key_points' => [
                $this->faker->sentence(),
                $this->faker->sentence(),
                $this->faker->sentence()
            ],
            'cache_key' => $this->faker->optional()->uuid(),
            'processing_started_at' => $this->faker->optional()->dateTimeBetween('-1 hour', 'now'),
            'processing_completed_at' => $this->faker->optional()->dateTimeBetween('-30 minutes', 'now'),
            'processing_time_ms' => $this->faker->optional()->numberBetween(1000, 30000),
            'error_message' => $this->faker->optional()->sentence(),
        ];
    }
    
    /**
     * Indicate that the summary is completed.
     */
    public function completed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'processing_completed_at' => now(),
                'processing_time_ms' => $this->faker->numberBetween(1000, 10000),
            ];
        });
    }
    
    /**
     * Indicate that the summary is processing.
     */
    public function processing(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'processing',
                'processing_started_at' => now(),
            ];
        });
    }
    
    /**
     * Indicate that the summary failed.
     */
    public function failed(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'error_message' => $this->faker->sentence(),
                'processing_completed_at' => now(),
            ];
        });
    }
    
    /**
     * Indicate that the summary is AI generated.
     */
    public function aiGenerated(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'ai_generated',
                'source' => $this->faker->randomElement(['gemini', 'local']),
            ];
        });
    }
    
    /**
     * Indicate that the summary is manual.
     */
    public function manual(): Factory
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'manual',
                'source' => 'manual',
            ];
        });
    }
}

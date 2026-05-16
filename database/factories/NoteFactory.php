<?php

namespace Database\Factories;

use App\Models\Note;
use App\Models\Article;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Note>
 */
class NoteFactory extends Factory
{
    protected $model = Note::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(),
            'content' => fake()->paragraphs(3, true),
            'user_id' => User::factory(),
            'article_id' => Article::factory(),
            'type' => fake()->randomElement(['personal', 'research', 'draft']),
            'is_private' => false,
            'is_rich_text' => false,
            'sync_status' => 'synced',
        ];
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'sync_status' => 'pending',
        ]);
    }

    public function richText(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_rich_text' => true,
            'content_json' => ['type' => 'doc', 'content' => []],
        ]);
    }

    public function private(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_private' => true,
        ]);
    }
}

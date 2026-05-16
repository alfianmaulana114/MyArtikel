<?php

namespace Database\Factories;

use App\Models\BookmarkCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<BookmarkCategory>
 */
class BookmarkCategoryFactory extends Factory
{
    protected $model = BookmarkCategory::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->words(2, true),
            'color' => fake()->hexColor(),
            'position' => 0,
            'is_public' => false,
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
            'name' => 'Default',
        ]);
    }
}

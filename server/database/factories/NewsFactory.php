<?php

namespace Database\Factories;

use App\Models\News;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<News>
 */
class NewsFactory extends Factory
{
    protected $model = News::class;

    public function definition(): array
    {
        return [
            'title' => fake()->sentence(4),
            'text' => fake()->paragraph(),
            'published_at' => fake()->date(),
            'is_published' => true,
            'sort_order' => 0,
            'image_path' => null,
        ];
    }

    /**
     * Неопубликованная новость.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}

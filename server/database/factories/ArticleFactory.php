<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * Фабрика для модели Article.
 *
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Article>
 */
class ArticleFactory extends Factory
{
    /**
     * Определить состояние модели по умолчанию.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->sentence(4);

        return [
            'slug' => Str::slug($title) . '-' . fake()->unique()->numberBetween(1, 99999),
            'title' => $title,
            'content' => '<p>' . implode('</p><p>', fake()->paragraphs(3)) . '</p>',
            'image_path' => null,
            'parent_id' => null,
            'is_published' => true,
            'sort_order' => 0,
            'meta_title' => null,
            'meta_description' => null,
        ];
    }

    /**
     * Неопубликованная статья.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}

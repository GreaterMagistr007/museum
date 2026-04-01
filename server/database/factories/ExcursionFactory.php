<?php

namespace Database\Factories;

use App\Models\Excursion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Excursion>
 */
class ExcursionFactory extends Factory
{
    protected $model = Excursion::class;

    public function definition(): array
    {
        $title = fake()->sentence(3);

        return [
            'slug' => Str::slug($title),
            'title' => $title,
            'short_title' => fake()->words(2, true),
            'short_description' => fake()->paragraph(),
            'duration_minutes' => fake()->numberBetween(30, 120),
            'group_size_min' => 5,
            'group_size_max' => 25,
            'description' => '<p>' . fake()->paragraph() . '</p>',
            'what_you_see' => '<p>' . fake()->paragraph() . '</p>',
            'interesting_facts' => '<p>' . fake()->paragraph() . '</p>',
            'image_path' => null,
            'is_published' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Неопубликованная экскурсия.
     */
    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}

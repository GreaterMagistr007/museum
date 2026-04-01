<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\Excursion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReorderControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Пересортировка требует авторизации.
     */
    public function test_reorder_requires_auth(): void
    {
        $response = $this->postJson('/admin/reorder/excursions', ['ids' => [1, 2]]);

        $response->assertStatus(401);
    }

    /**
     * Пересортировка экскурсий обновляет sort_order.
     */
    public function test_reorder_excursions(): void
    {
        $user = User::factory()->create();

        $e1 = Excursion::factory()->create(['sort_order' => 0]);
        $e2 = Excursion::factory()->create(['slug' => 'second', 'sort_order' => 1]);
        $e3 = Excursion::factory()->create(['slug' => 'third', 'sort_order' => 2]);

        // Обратный порядок
        $response = $this->actingAs($user)->postJson('/admin/reorder/excursions', [
            'ids' => [$e3->id, $e2->id, $e1->id],
        ]);

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('excursions', ['id' => $e3->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('excursions', ['id' => $e2->id, 'sort_order' => 1]);
        $this->assertDatabaseHas('excursions', ['id' => $e1->id, 'sort_order' => 2]);
    }

    /**
     * Пересортировка каталога экспозиции обновляет sort_order только для нужного type.
     */
    public function test_reorder_catalog_exposition(): void
    {
        $user = User::factory()->create();

        $item1 = new CatalogItem(['title' => 'A', 'description' => 'D', 'sort_order' => 0]);
        $item1->type = 'exposition';
        $item1->save();

        $item2 = new CatalogItem(['title' => 'B', 'description' => 'D', 'sort_order' => 1]);
        $item2->type = 'exposition';
        $item2->save();

        // Поменять местами
        $response = $this->actingAs($user)->postJson('/admin/reorder/catalog-exposition', [
            'ids' => [$item2->id, $item1->id],
        ]);

        $response->assertJson(['success' => true]);

        $this->assertDatabaseHas('catalog_items', ['id' => $item2->id, 'sort_order' => 0]);
        $this->assertDatabaseHas('catalog_items', ['id' => $item1->id, 'sort_order' => 1]);
    }

    /**
     * Несуществующая сущность возвращает 404.
     */
    public function test_reorder_invalid_entity_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/admin/reorder/invalid-entity', [
            'ids' => [1, 2],
        ]);

        $response->assertStatus(404);
    }

    /**
     * Валидация ids — обязательный массив целых чисел.
     */
    public function test_reorder_validates_ids(): void
    {
        $user = User::factory()->create();

        // Пустой запрос
        $response = $this->actingAs($user)->postJson('/admin/reorder/excursions', []);
        $response->assertStatus(422);
        $response->assertJsonValidationErrors('ids');

        // Строки вместо целых
        $response = $this->actingAs($user)->postJson('/admin/reorder/excursions', [
            'ids' => ['abc', 'def'],
        ]);
        $response->assertStatus(422);
    }
}

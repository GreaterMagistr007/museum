<?php

namespace Tests\Feature;

use App\Models\CatalogItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CatalogControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Список каталога требует авторизации.
     */
    public function test_catalog_index_requires_auth(): void
    {
        $response = $this->get('/admin/catalog/exposition');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Авторизованный пользователь видит список экспозиции.
     * Колонка title для exposition заменена на превью описания.
     */
    public function test_catalog_exposition_index(): void
    {
        $user = User::factory()->create();

        $item = new CatalogItem([
            'description' => 'AdminExpoDescriptionMarker',
            'is_published' => true,
        ]);
        $item->type = 'exposition';
        $item->save();

        $response = $this->actingAs($user)->get('/admin/catalog/exposition');

        $response->assertStatus(200);
        $response->assertSee('AdminExpoDescriptionMarker');
    }

    /**
     * Авторизованный пользователь видит список архива.
     */
    public function test_catalog_archive_index(): void
    {
        $user = User::factory()->create();

        $item = new CatalogItem([
            'title' => 'Тестовый архив',
            'description' => 'Описание',
            'is_published' => true,
        ]);
        $item->type = 'archive';
        $item->save();

        $response = $this->actingAs($user)->get('/admin/catalog/archive');

        $response->assertStatus(200);
        $response->assertSee('Тестовый архив');
    }

    /**
     * Создание элемента каталога. Для exposition title и link_url
     * принудительно сбрасываются в null контроллером.
     */
    public function test_catalog_store_creates_item(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/catalog/exposition', [
            'title' => 'Игнорируемый для exposition заголовок',
            'description' => 'Описание нового элемента.',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/catalog/exposition');
        $this->assertDatabaseHas('catalog_items', [
            'type' => 'exposition',
            'title' => null,
            'description' => 'Описание нового элемента.',
        ]);
    }

    /**
     * Валидация link_url: javascript: должен быть отклонён.
     * Используем тип archive, т.к. для exposition link_url принудительно сбрасывается.
     */
    public function test_catalog_store_validates_link_url_protocol(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/catalog/archive', [
            'title' => 'Элемент с XSS',
            'description' => 'Описание.',
            'link_url' => 'javascript:alert(1)',
            'is_published' => 1,
        ]);

        $response->assertSessionHasErrors('link_url');
    }

    /**
     * Обновление элемента каталога (archive — для exposition title не используется).
     */
    public function test_catalog_update_works(): void
    {
        $user = User::factory()->create();

        $item = new CatalogItem([
            'title' => 'Старое название',
            'description' => 'Описание',
            'is_published' => true,
        ]);
        $item->type = 'archive';
        $item->save();

        $response = $this->actingAs($user)->put('/admin/catalog/archive/' . $item->id, [
            'title' => 'Новое название',
            'description' => 'Обновлённое описание',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/catalog/archive');
        $this->assertDatabaseHas('catalog_items', [
            'id' => $item->id,
            'title' => 'Новое название',
        ]);
    }

    /**
     * Удаление элемента каталога.
     */
    public function test_catalog_destroy_deletes(): void
    {
        $user = User::factory()->create();

        $item = new CatalogItem([
            'title' => 'Удаляемый элемент',
            'description' => 'Описание',
            'is_published' => true,
        ]);
        $item->type = 'archive';
        $item->save();

        $response = $this->actingAs($user)->delete('/admin/catalog/archive/' . $item->id);

        $response->assertRedirect('/admin/catalog/archive');
        $this->assertDatabaseMissing('catalog_items', ['id' => $item->id]);
    }

    /**
     * Публичная страница экспозиции показывает описания опубликованных элементов.
     * Title для exposition не выводится — маркер ищем в description.
     */
    public function test_public_exposition_page(): void
    {
        $published = new CatalogItem([
            'description' => 'PublicExpoVisibleMarker',
            'is_published' => true,
            'sort_order' => 0,
        ]);
        $published->type = 'exposition';
        $published->save();

        $unpublished = new CatalogItem([
            'description' => 'PublicExpoHiddenMarker',
            'is_published' => false,
            'sort_order' => 1,
        ]);
        $unpublished->type = 'exposition';
        $unpublished->save();

        $response = $this->get('/exposition');

        $response->assertStatus(200);
        $response->assertSee('PublicExpoVisibleMarker');
        $response->assertDontSee('PublicExpoHiddenMarker');
    }

    /**
     * Публичная страница архива показывает опубликованные элементы.
     */
    public function test_public_archive_page(): void
    {
        $published = new CatalogItem([
            'title' => 'Опубликованный архив',
            'description' => 'Описание',
            'is_published' => true,
            'sort_order' => 0,
        ]);
        $published->type = 'archive';
        $published->save();

        $unpublished = new CatalogItem([
            'title' => 'Скрытый архив',
            'description' => 'Описание',
            'is_published' => false,
            'sort_order' => 1,
        ]);
        $unpublished->type = 'archive';
        $unpublished->save();

        $response = $this->get('/archive');

        $response->assertStatus(200);
        $response->assertSee('Опубликованный архив');
        $response->assertDontSee('Скрытый архив');
    }

    /**
     * Невалидный тип каталога возвращает 404.
     */
    public function test_catalog_invalid_type_returns_404(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/catalog/invalid');

        $response->assertStatus(404);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ArticleControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Список статей в админке требует авторизации.
     */
    public function test_article_index_requires_auth(): void
    {
        $response = $this->get('/admin/articles');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Создание статьи через POST.
     */
    public function test_article_store_creates_record(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/articles', [
            'slug' => 'test-article',
            'title' => 'Тестовая статья',
            'content' => '<p>Содержимое тестовой статьи.</p>',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/articles');
        $this->assertDatabaseHas('articles', ['slug' => 'test-article', 'title' => 'Тестовая статья']);
    }

    /**
     * Валидация уникальности slug.
     */
    public function test_article_store_validates_slug_unique(): void
    {
        $user = User::factory()->create();
        Article::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($user)->post('/admin/articles', [
            'slug' => 'existing-slug',
            'title' => 'Другая статья',
            'content' => '<p>Описание.</p>',
            'is_published' => 1,
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    /**
     * Обновление статьи через PUT.
     */
    public function test_article_update_works(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create(['slug' => 'old-slug', 'title' => 'Старое название']);

        $response = $this->actingAs($user)->put('/admin/articles/' . $article->slug, [
            'slug' => 'old-slug',
            'title' => 'Новое название',
            'content' => '<p>Содержимое.</p>',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/articles');
        $this->assertDatabaseHas('articles', ['id' => $article->id, 'title' => 'Новое название']);
    }

    /**
     * Soft delete при удалении статьи.
     */
    public function test_article_destroy_soft_deletes(): void
    {
        $user = User::factory()->create();
        $article = Article::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/articles/' . $article->slug);

        $response->assertRedirect('/admin/articles');
        $this->assertSoftDeleted('articles', ['id' => $article->id]);
    }

    /**
     * Публичная страница статьи возвращает 200.
     */
    public function test_public_article_show(): void
    {
        $article = Article::factory()->create(['slug' => 'show-test', 'is_published' => true]);

        $response = $this->get('/article/show-test');

        $response->assertStatus(200);
        $response->assertSee($article->title);
    }

    /**
     * Неопубликованная статья возвращает 404.
     */
    public function test_article_show_404_for_unpublished(): void
    {
        Article::factory()->create(['slug' => 'hidden-test', 'is_published' => false]);

        $response = $this->get('/article/hidden-test');

        $response->assertStatus(404);
    }

    /**
     * Дочерняя статья отображает хлебные крошки с родителем.
     */
    public function test_article_show_child_has_parent_breadcrumb(): void
    {
        $parent = Article::factory()->create([
            'slug' => 'parent-article',
            'title' => 'Родительская статья',
            'is_published' => true,
        ]);
        $child = Article::factory()->create([
            'slug' => 'child-article',
            'title' => 'Дочерняя статья',
            'parent_id' => $parent->id,
            'is_published' => true,
        ]);

        $response = $this->get('/article/child-article');

        $response->assertStatus(200);
        $response->assertSee('Родительская статья');
        $response->assertSee('Дочерняя статья');
    }

    /**
     * 301-редирект со старого URL /military-town.
     */
    public function test_military_town_redirects_to_article(): void
    {
        $response = $this->get('/military-town');

        $response->assertRedirect('/article/military-town');
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * 301-редирект со старого URL /junker-school.
     */
    public function test_junker_school_redirects_to_article(): void
    {
        $response = $this->get('/junker-school');

        $response->assertRedirect('/article/junker-school');
        $this->assertEquals(301, $response->getStatusCode());
    }

    /**
     * Импорт docx требует авторизации.
     */
    public function test_article_import_requires_auth(): void
    {
        $response = $this->post('/admin/articles/import');

        $response->assertRedirect('/admin/login');
    }
}

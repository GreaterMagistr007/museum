<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\CatalogItem;
use App\Models\Excursion;
use App\Models\News;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * E2E интеграционные тесты CMS.
 *
 * Каждый тест имитирует полный пользовательский сценарий:
 * создание/изменение контента через админку -> проверка отображения на публичном сайте.
 */
class CmsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    // =========================================================================
    // 1. Настройки -> Публичные страницы
    // =========================================================================

    /**
     * Изменение телефона в настройках отражается на странице контактов.
     */
    public function test_changing_contact_phone_reflects_on_contacts_page(): void
    {
        $this->actingAs($this->user)->put('/admin/settings', [
            'contacts' => ['phone' => '+7 (999) 123-45-67'],
        ]);

        $response = $this->get('/contacts');

        $response->assertStatus(200);
        $response->assertSee('+7 (999) 123-45-67');
    }

    /**
     * Изменение расписания в настройках отражается на странице «О музее».
     */
    public function test_changing_schedule_reflects_on_about_page(): void
    {
        $this->actingAs($this->user)->put('/admin/settings', [
            'schedule' => ['weekdays' => '10:00 – 18:00'],
        ]);

        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('10:00 – 18:00');
    }

    /**
     * Изменение модального окна «О музее» отражается на главной странице.
     */
    public function test_changing_modals_about_reflects_on_home_page(): void
    {
        $this->actingAs($this->user)->put('/admin/settings', [
            'modals' => ['about' => '<p>Новое описание музея</p>'],
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('Новое описание музея', false);
    }

    // =========================================================================
    // 2. Новости -> Публичная страница
    // =========================================================================

    /**
     * Создание новости через админку -> отображение на публичной странице.
     */
    public function test_creating_news_shows_on_public_page(): void
    {
        $this->actingAs($this->user)->post('/admin/news', [
            'title' => 'Тестовая новость E2E',
            'text' => 'Текст тестовой новости для E2E',
            'published_at' => now()->format('Y-m-d'),
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('news', ['title' => 'Тестовая новость E2E']);

        $response = $this->get('/news');

        $response->assertStatus(200);
        $response->assertSee('Тестовая новость E2E');
        $response->assertSee('Текст тестовой новости для E2E');
    }

    /**
     * Снятие с публикации скрывает новость с публичной страницы.
     */
    public function test_unpublishing_news_hides_from_public(): void
    {
        $news = News::factory()->create([
            'title' => 'Новость для снятия',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        // Убеждаемся, что новость видна
        $response = $this->get('/news');
        $response->assertSee('Новость для снятия');

        // Снимаем с публикации
        $this->actingAs($this->user)->put('/admin/news/' . $news->id, [
            'title' => $news->title,
            'text' => $news->text,
            'published_at' => $news->published_at->format('Y-m-d'),
            'is_published' => 0,
        ]);

        // Проверяем, что новость скрыта
        $response = $this->get('/news');
        $response->assertDontSee('Новость для снятия');
    }

    /**
     * Удаление новости скрывает её с публичной страницы.
     */
    public function test_deleting_news_hides_from_public(): void
    {
        $news = News::factory()->create([
            'title' => 'Новость для удаления',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);

        // Убеждаемся, что новость видна
        $response = $this->get('/news');
        $response->assertSee('Новость для удаления');

        // Удаляем через админку
        $this->actingAs($this->user)->delete('/admin/news/' . $news->id);

        // Проверяем, что новость скрыта
        $response = $this->get('/news');
        $response->assertDontSee('Новость для удаления');
    }

    // =========================================================================
    // 3. Экскурсии -> Публичная страница
    // =========================================================================

    /**
     * Создание экскурсии через админку -> отображение в списке и на детальной странице.
     */
    public function test_creating_excursion_shows_on_public_pages(): void
    {
        $this->actingAs($this->user)->post('/admin/excursions', [
            'slug' => 'test-e2e',
            'title' => 'E2E экскурсия',
            'short_description' => 'Краткое описание E2E экскурсии',
            'duration_minutes' => 60,
            'group_size_min' => 5,
            'group_size_max' => 20,
            'description' => '<p>Полное описание E2E экскурсии</p>',
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('excursions', ['slug' => 'test-e2e']);

        // Проверяем список экскурсий
        $response = $this->get('/excursions');
        $response->assertStatus(200);
        $response->assertSee('E2E экскурсия');

        // Проверяем детальную страницу
        $response = $this->get('/excursion/test-e2e');
        $response->assertStatus(200);
        $response->assertSee('Полное описание E2E экскурсии', false);
        $response->assertSee('60 минут');
    }

    /**
     * Обновление описания экскурсии отражается на детальной странице.
     */
    public function test_updating_excursion_description_reflects_on_detail_page(): void
    {
        $excursion = Excursion::factory()->create([
            'slug' => 'update-test',
            'is_published' => true,
        ]);

        $this->actingAs($this->user)->put('/admin/excursions/' . $excursion->slug, [
            'slug' => $excursion->slug,
            'title' => $excursion->title,
            'short_description' => $excursion->short_description,
            'duration_minutes' => $excursion->duration_minutes,
            'group_size_min' => $excursion->group_size_min,
            'group_size_max' => $excursion->group_size_max,
            'description' => '<p>Обновлённое описание экскурсии</p>',
            'is_published' => 1,
        ]);

        $response = $this->get('/excursion/update-test');

        $response->assertStatus(200);
        $response->assertSee('Обновлённое описание экскурсии', false);
    }

    // =========================================================================
    // 4. Статьи -> Публичная страница
    // =========================================================================

    /**
     * Создание статьи через админку -> отображение на публичной странице.
     */
    public function test_creating_article_shows_on_public(): void
    {
        $this->actingAs($this->user)->post('/admin/articles', [
            'slug' => 'test-article',
            'title' => 'E2E Статья',
            'content' => '<p>Содержимое статьи E2E</p>',
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('articles', ['slug' => 'test-article']);

        $response = $this->get('/article/test-article');

        $response->assertStatus(200);
        $response->assertSee('E2E Статья');
        $response->assertSee('Содержимое статьи E2E', false);
    }

    /**
     * 301-редирект со старого URL /military-town продолжает работать.
     */
    public function test_article_301_redirect_still_works(): void
    {
        // Редирект настроен в роутах и не зависит от наличия статьи в БД
        $response = $this->get('/military-town');

        $response->assertRedirect('/article/military-town');
        $this->assertEquals(301, $response->getStatusCode());
    }

    // =========================================================================
    // 5. Каталог -> Публичная страница
    // =========================================================================

    /**
     * Создание элемента экспозиции через админку -> отображение на публичной странице.
     */
    public function test_creating_exposition_item_shows_on_public(): void
    {
        $this->actingAs($this->user)->post('/admin/catalog/exposition', [
            'title' => 'E2E Экспонат',
            'description' => 'Описание экспоната E2E',
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('catalog_items', [
            'title' => 'E2E Экспонат',
            'type' => 'exposition',
        ]);

        $response = $this->get('/exposition');

        $response->assertStatus(200);
        $response->assertSee('E2E Экспонат');
    }

    /**
     * Создание элемента архива через админку -> отображение на публичной странице.
     */
    public function test_creating_archive_item_shows_on_public(): void
    {
        $this->actingAs($this->user)->post('/admin/catalog/archive', [
            'title' => 'E2E Архив',
            'description' => 'Описание архива E2E',
            'is_published' => 1,
        ]);

        $this->assertDatabaseHas('catalog_items', [
            'title' => 'E2E Архив',
            'type' => 'archive',
        ]);

        $response = $this->get('/archive');

        $response->assertStatus(200);
        $response->assertSee('E2E Архив');
    }

    // =========================================================================
    // 6. HTML-санитизация в действии
    // =========================================================================

    /**
     * XSS в настройках санитизируется: script-теги удаляются из БД и со страницы.
     */
    public function test_xss_in_settings_is_sanitized(): void
    {
        $this->actingAs($this->user)->put('/admin/settings', [
            'modals' => ['about' => '<p>Текст</p><script>alert("xss")</script>'],
        ]);

        // Проверяем, что в БД нет script-тега
        $value = Setting::get('modals.about');
        $this->assertStringNotContainsString('<script>', $value);
        $this->assertStringContainsString('<p>Текст</p>', $value);

        // Проверяем, что на главной странице нет script-тега
        $response = $this->get('/');
        $response->assertDontSee('<script>', false);
        $response->assertSee('Текст', false);
    }

    /**
     * XSS в описании экскурсии санитизируется: script и iframe удаляются.
     */
    public function test_xss_in_excursion_description_is_sanitized(): void
    {
        $this->actingAs($this->user)->post('/admin/excursions', [
            'slug' => 'xss-test',
            'title' => 'XSS тест',
            'short_description' => 'Описание',
            'duration_minutes' => 30,
            'group_size_min' => 5,
            'group_size_max' => 20,
            'description' => '<p>OK</p><script>alert(1)</script><iframe src="evil"></iframe>',
            'is_published' => 1,
        ]);

        $excursion = Excursion::where('slug', 'xss-test')->first();
        $this->assertNotNull($excursion);
        $this->assertStringNotContainsString('<script>', $excursion->description);
        $this->assertStringNotContainsString('<iframe', $excursion->description);
        $this->assertStringContainsString('<p>OK</p>', $excursion->description);
    }

    /**
     * XSS в контенте статьи санитизируется: script-теги удаляются.
     */
    public function test_xss_in_article_content_is_sanitized(): void
    {
        $this->actingAs($this->user)->post('/admin/articles', [
            'slug' => 'xss-article',
            'title' => 'XSS статья',
            'content' => '<p>Статья</p><script>document.cookie</script>',
            'is_published' => 1,
        ]);

        $article = Article::where('slug', 'xss-article')->first();
        $this->assertNotNull($article);
        $this->assertStringNotContainsString('<script>', $article->content);
        $this->assertStringContainsString('<p>Статья</p>', $article->content);
    }

    // =========================================================================
    // 7. Загрузка изображений
    // =========================================================================

    /**
     * Загрузка изображения при создании новости -> отображение на публичной странице.
     */
    public function test_news_image_upload_and_display(): void
    {
        Storage::fake();

        // Минимальный валидный JPEG 1x1 (без GD)
        $soi = "\xFF\xD8";
        $app0 = "\xFF\xE0" . pack('n', 16) . "JFIF\x00\x01\x02\x00" . pack('n', 72) . pack('n', 72) . "\x00\x00";
        $sof = "\xFF\xC0" . pack('n', 11) . "\x08" . pack('n', 1) . pack('n', 1) . "\x01\x01\x11\x00";
        $dht = "\xFF\xC4" . pack('n', 31) . str_repeat("\x00", 29);
        $sos = "\xFF\xDA" . pack('n', 8) . "\x01\x01\x00\x00\x3F\x00\x7F\x50";
        $eoi = "\xFF\xD9";
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tmpPath, $soi . $app0 . $sof . $dht . $sos . $eoi);
        $file = new UploadedFile($tmpPath, 'test.jpg', 'image/jpeg', null, true);

        $this->actingAs($this->user)->post('/admin/news', [
            'title' => 'Новость с изображением',
            'text' => 'Текст новости с фото.',
            'published_at' => now()->format('Y-m-d'),
            'is_published' => 1,
            'image' => $file,
        ]);

        $news = News::where('title', 'Новость с изображением')->first();
        $this->assertNotNull($news);
        $this->assertNotNull($news->image_path);
        Storage::assertExists($news->image_path);

        // Проверяем, что на публичной странице есть ссылка на изображение
        $response = $this->get('/news');
        $response->assertStatus(200);
        $response->assertSee('uploads/news/', false);
    }
}

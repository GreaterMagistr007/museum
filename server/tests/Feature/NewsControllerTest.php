<?php

namespace Tests\Feature;

use App\Models\News;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class NewsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Список новостей в админке требует авторизации.
     */
    public function test_news_index_requires_auth(): void
    {
        $response = $this->get('/admin/news');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Авторизованный пользователь видит список новостей.
     */
    public function test_news_index_shows_list(): void
    {
        $user = User::factory()->create();
        $news = News::factory()->count(3)->create();

        $response = $this->actingAs($user)->get('/admin/news');

        $response->assertStatus(200);
        foreach ($news as $item) {
            $response->assertSee($item->title);
        }
    }

    /**
     * Создание новости через POST.
     */
    public function test_news_store_creates_record(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/news', [
            'title' => 'Тестовая новость',
            'text' => 'Текст тестовой новости.',
            'published_at' => '2026-03-01',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/news');
        $this->assertDatabaseHas('news', ['title' => 'Тестовая новость']);
    }

    /**
     * Валидация обязательных полей при создании.
     */
    public function test_news_store_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/news', []);

        $response->assertSessionHasErrors(['title', 'text', 'published_at']);
    }

    /**
     * Обновление новости через PUT.
     */
    public function test_news_update_modifies_record(): void
    {
        $user = User::factory()->create();
        $news = News::factory()->create(['title' => 'Старый заголовок']);

        $response = $this->actingAs($user)->put('/admin/news/' . $news->id, [
            'title' => 'Новый заголовок',
            'text' => $news->text,
            'published_at' => $news->published_at->format('Y-m-d'),
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/news');
        $this->assertDatabaseHas('news', ['id' => $news->id, 'title' => 'Новый заголовок']);
    }

    /**
     * Soft delete при удалении.
     */
    public function test_news_destroy_soft_deletes(): void
    {
        $user = User::factory()->create();
        $news = News::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/news/' . $news->id);

        $response->assertRedirect('/admin/news');
        $this->assertSoftDeleted('news', ['id' => $news->id]);
    }

    /**
     * Публичная страница показывает только опубликованные новости.
     */
    public function test_public_news_page_shows_published(): void
    {
        $published = News::factory()->create([
            'title' => 'Опубликованная',
            'is_published' => true,
            'published_at' => now()->subDay(),
        ]);
        $unpublished = News::factory()->create([
            'title' => 'Черновик',
            'is_published' => false,
            'published_at' => now()->subDay(),
        ]);

        $response = $this->get('/news');

        $response->assertStatus(200);
        $response->assertSee('Опубликованная');
        $response->assertDontSee('Черновик');
    }

    /**
     * Создание новости с изображением.
     */
    public function test_news_store_with_image(): void
    {
        // Default disk = local, storeAs пишет в local disk
        Storage::fake();

        $user = User::factory()->create();

        // Минимальный валидный JPEG 1x1 (без GD)
        $soi = "\xFF\xD8";
        $app0 = "\xFF\xE0" . pack('n', 16) . "JFIF\x00\x01\x02\x00" . pack('n', 72) . pack('n', 72) . "\x00\x00";
        $sof = "\xFF\xC0" . pack('n', 11) . "\x08" . pack('n', 1) . pack('n', 1) . "\x01\x01\x11\x00";
        $dht = "\xFF\xC4" . pack('n', 31) . str_repeat("\x00", 29);
        $sos = "\xFF\xDA" . pack('n', 8) . "\x01\x01\x00\x00\x3F\x00\x7F\x50";
        $eoi = "\xFF\xD9";
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tmpPath, $soi . $app0 . $sof . $dht . $sos . $eoi);
        $file = new UploadedFile($tmpPath, 'photo.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($user)->post('/admin/news', [
            'title' => 'Новость с фото',
            'text' => 'Текст новости с фото.',
            'published_at' => '2026-03-01',
            'is_published' => 1,
            'image' => $file,
        ]);

        $response->assertRedirect('/admin/news');

        $news = News::where('title', 'Новость с фото')->first();
        $this->assertNotNull($news->image_path);
        Storage::assertExists($news->image_path);
    }
}

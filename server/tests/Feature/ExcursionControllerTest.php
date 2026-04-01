<?php

namespace Tests\Feature;

use App\Models\Excursion;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ExcursionControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Список экскурсий в админке требует авторизации.
     */
    public function test_excursion_index_requires_auth(): void
    {
        $response = $this->get('/admin/excursions');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Создание экскурсии через POST.
     */
    public function test_excursion_store_creates_record(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/admin/excursions', [
            'slug' => 'test-excursion',
            'title' => 'Тестовая экскурсия',
            'short_description' => 'Краткое описание тестовой экскурсии.',
            'duration_minutes' => 60,
            'group_size_min' => 5,
            'group_size_max' => 25,
            'description' => '<p>Полное описание тестовой экскурсии.</p>',
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/excursions');
        $this->assertDatabaseHas('excursions', ['slug' => 'test-excursion', 'title' => 'Тестовая экскурсия']);
    }

    /**
     * Валидация уникальности slug.
     */
    public function test_excursion_store_validates_slug_unique(): void
    {
        $user = User::factory()->create();
        Excursion::factory()->create(['slug' => 'existing-slug']);

        $response = $this->actingAs($user)->post('/admin/excursions', [
            'slug' => 'existing-slug',
            'title' => 'Другая экскурсия',
            'short_description' => 'Описание.',
            'duration_minutes' => 30,
            'group_size_min' => 5,
            'group_size_max' => 20,
            'description' => '<p>Описание.</p>',
            'is_published' => 1,
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    /**
     * Обновление экскурсии через PUT.
     */
    public function test_excursion_update_works(): void
    {
        $user = User::factory()->create();
        $excursion = Excursion::factory()->create(['slug' => 'old-slug', 'title' => 'Старое название']);

        $response = $this->actingAs($user)->put('/admin/excursions/' . $excursion->slug, [
            'slug' => 'old-slug',
            'title' => 'Новое название',
            'short_description' => $excursion->short_description,
            'duration_minutes' => $excursion->duration_minutes,
            'group_size_min' => $excursion->group_size_min,
            'group_size_max' => $excursion->group_size_max,
            'description' => $excursion->description,
            'is_published' => 1,
        ]);

        $response->assertRedirect('/admin/excursions');
        $this->assertDatabaseHas('excursions', ['id' => $excursion->id, 'title' => 'Новое название']);
    }

    /**
     * Soft delete при удалении экскурсии.
     */
    public function test_excursion_destroy_soft_deletes(): void
    {
        $user = User::factory()->create();
        $excursion = Excursion::factory()->create();

        $response = $this->actingAs($user)->delete('/admin/excursions/' . $excursion->slug);

        $response->assertRedirect('/admin/excursions');
        $this->assertSoftDeleted('excursions', ['id' => $excursion->id]);
    }

    /**
     * Публичная страница списка экскурсий.
     */
    public function test_public_excursions_page(): void
    {
        $published = Excursion::factory()->create(['title' => 'Открытая экскурсия', 'is_published' => true]);
        $unpublished = Excursion::factory()->create(['slug' => 'hidden', 'title' => 'Скрытая экскурсия', 'is_published' => false]);

        $response = $this->get('/excursions');

        $response->assertStatus(200);
        $response->assertSee('Открытая экскурсия');
        $response->assertDontSee('Скрытая экскурсия');
    }

    /**
     * Публичная страница детальной экскурсии.
     */
    public function test_public_excursion_show(): void
    {
        $excursion = Excursion::factory()->create(['slug' => 'show-test', 'is_published' => true]);

        $response = $this->get('/excursion/show-test');

        $response->assertStatus(200);
        $response->assertSee($excursion->title);
    }

    /**
     * Неопубликованная экскурсия возвращает 404.
     */
    public function test_excursion_show_404_for_unpublished(): void
    {
        Excursion::factory()->create(['slug' => 'hidden-test', 'is_published' => false]);

        $response = $this->get('/excursion/hidden-test');

        $response->assertStatus(404);
    }

    /**
     * Загрузка изображения через WYSIWYG.
     */
    public function test_wysiwyg_image_upload(): void
    {
        Storage::fake();

        $user = User::factory()->create();

        // Минимальный валидный JPEG 1x1
        $soi = "\xFF\xD8";
        $app0 = "\xFF\xE0" . pack('n', 16) . "JFIF\x00\x01\x02\x00" . pack('n', 72) . pack('n', 72) . "\x00\x00";
        $sof = "\xFF\xC0" . pack('n', 11) . "\x08" . pack('n', 1) . pack('n', 1) . "\x01\x01\x11\x00";
        $dht = "\xFF\xC4" . pack('n', 31) . str_repeat("\x00", 29);
        $sos = "\xFF\xDA" . pack('n', 8) . "\x01\x01\x00\x00\x3F\x00\x7F\x50";
        $eoi = "\xFF\xD9";
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tmpPath, $soi . $app0 . $sof . $dht . $sos . $eoi);
        $file = new UploadedFile($tmpPath, 'photo.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($user)->post('/admin/upload/image', [
            'file' => $file,
        ]);

        $response->assertStatus(200);
        $response->assertJsonStructure(['location']);
    }
}

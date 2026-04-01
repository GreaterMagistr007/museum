<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SettingsControllerTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Страница настроек требует авторизации.
     */
    public function test_settings_requires_auth(): void
    {
        $response = $this->get('/admin/settings');

        $response->assertRedirect('/admin/login');
    }

    /**
     * Авторизованный пользователь видит форму настроек.
     */
    public function test_settings_edit_page_loads(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/admin/settings');

        $response->assertStatus(200);
        $response->assertSee('Настройки сайта');
        $response->assertSee('Контакты');
        $response->assertSee('О музее');
    }

    /**
     * Сохранение настроек записывает значения в БД.
     */
    public function test_settings_update_saves_values(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/admin/settings', [
            'contacts' => ['phone' => '+7 (999) 123-45-67'],
            'schedule' => ['weekdays' => '10:00 – 18:00'],
        ]);

        $response->assertRedirect('/admin/settings');
        $this->assertDatabaseHas('settings', ['key' => 'contacts.phone', 'value' => '+7 (999) 123-45-67']);
        $this->assertDatabaseHas('settings', ['key' => 'schedule.weekdays', 'value' => '10:00 – 18:00']);
    }

    /**
     * HTML-поля санитизируются: script-теги удаляются.
     */
    public function test_settings_sanitizes_html(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->put('/admin/settings', [
            'modals' => ['about' => '<p>Текст</p><script>alert("xss")</script>'],
        ]);

        $response->assertRedirect('/admin/settings');
        $setting = Setting::where('key', 'modals.about')->first();
        $this->assertNotNull($setting);
        $this->assertStringContainsString('<p>Текст</p>', $setting->value);
        $this->assertStringNotContainsString('<script>', $setting->value);
    }

    /**
     * Страница «О музее» отображает контент из настроек.
     */
    public function test_about_page_shows_content_from_db(): void
    {
        Setting::set('about.history', '<p>Тестовая история</p>');
        Setting::set('about.mission', '<p>Тестовая миссия</p>');

        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertSee('Тестовая история', false);
        $response->assertSee('Тестовая миссия', false);
    }

    /**
     * Главная страница загружается.
     */
    public function test_home_page_loads(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Загрузка изображения здания через настройки.
     */
    public function test_settings_upload_building_image(): void
    {
        Storage::fake();
        $user = User::factory()->create();

        // Минимальный валидный JPEG
        $soi = "\xFF\xD8";
        $app0 = "\xFF\xE0" . pack('n', 16) . "JFIF\x00\x01\x02\x00" . pack('n', 72) . pack('n', 72) . "\x00\x00";
        $sof = "\xFF\xC0" . pack('n', 11) . "\x08" . pack('n', 1) . pack('n', 1) . "\x01\x01\x11\x00";
        $dht = "\xFF\xC4" . pack('n', 31) . str_repeat("\x00", 29);
        $sos = "\xFF\xDA" . pack('n', 8) . "\x01\x01\x00\x00\x3F\x00\x7F\x50";
        $eoi = "\xFF\xD9";
        $tmpPath = tempnam(sys_get_temp_dir(), 'test_img_');
        file_put_contents($tmpPath, $soi . $app0 . $sof . $dht . $sos . $eoi);
        $file = new UploadedFile($tmpPath, 'building.jpg', 'image/jpeg', null, true);

        $response = $this->actingAs($user)->put('/admin/settings', [
            'home_building_image' => $file,
        ]);

        $response->assertRedirect('/admin/settings');
        $setting = Setting::where('key', 'home.building_image')->first();
        $this->assertNotNull($setting);
        $this->assertNotNull($setting->value);
        Storage::assertExists($setting->value);
    }

    /**
     * Удаление изображения здания через чекбокс.
     */
    public function test_settings_remove_building_image(): void
    {
        Storage::fake();
        $user = User::factory()->create();
        Setting::set('home.building_image', 'public/uploads/settings/old.jpg');

        $response = $this->actingAs($user)->put('/admin/settings', [
            'remove_building_image' => '1',
        ]);

        $response->assertRedirect('/admin/settings');
        $setting = Setting::where('key', 'home.building_image')->first();
        $this->assertNull($setting->value);
    }
}

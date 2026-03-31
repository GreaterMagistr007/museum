# Технический план реализации CMS -- Музей "Иркутское юнкерское училище"

Проект: Laravel 11.48, PHP 8.2+, SQLite (dev) / MySQL (prod), Blade SSR, vanilla CSS (BEM), vanilla JS.
Рабочая директория: `server/`.
Исходный план: `plan.md`.

---

## Общие сквозные правила (применяются ко всем фазам)

### HTML-санитизация (Purify)

Пакет `stevebauman/purify` подключается в Фазе 1 (для modals.about содержащего HTML) и используется во всех последующих.

Конфигурация `config/purify.php`:

```php
'default' => 'default',
'configs' => [
    'default' => [
        'HTML.Allowed' => 'p,br,strong,b,em,i,u,h2,h3,h4,ul,ol,li,a[href|target|rel],img[src|alt|width|height|loading],blockquote,table,thead,tbody,tr,td,th,figure[class],figcaption',
        'HTML.ForbiddenElements' => 'script,iframe,form,input,object,embed',
        'AutoFormat.AutoParagraph' => false,
        'AutoFormat.RemoveEmpty' => false,
        'URI.AllowedSchemes' => ['http', 'https', 'mailto'],
        'Attr.AllowedFrameTargets' => ['_blank'],
        'HTML.TargetBlank' => true,
    ],
],
'custom_definition' => [
    'id' => 'custom-html5',
    'elements' => [
        ['figure', 'Block', 'Flow', 'Common', ['class' => 'CDATA']],
        ['figcaption', 'Block', 'Flow', 'Common', []],
    ],
],
```

Санитизация через мутатор на модели (см. каждую модель).

### Загрузка файлов

Правила валидации (одинаковые везде):
```php
'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
```

Имя файла: `Str::uuid() . '.' . $file->extension()`.
Хранение: `storage/app/public/uploads/{subdir}/`.
Публичный URL: `/storage/uploads/{subdir}/{filename}`.

### Удаление записей с SoftDeletes

- `deleting` event: НЕ удалять файлы.
- `forceDeleting` event: удалять файлы через `ImageUploadService::delete()`.
- JS confirm() на кнопке удаления: `confirm("Удалить {тип} '{title}'?")`.

### Сидеры

- `updateOrCreate()` внутри `DB::transaction()`.
- Идемпотентные: повторный запуск безопасен.
- Для articles: копируется содержимое `<div class="article">...</div>` (без Blade-директив, без `<h2>`, без breadcrumbs).

### $fillable

Явно перечисляется для каждой модели. Никаких `$guarded = []`.

### Тесты

Feature-тесты в каждой фазе: CRUD + валидация + auth redirect для admin, корректный response для public.

---

## Фаза 1: Admin layout + Настройки сайта

**Цель**: превратить dashboard-заглушку в рабочую панель с sidebar-навигацией. Вынести контакты, расписание, данные модалок в БД.

---

### 1.1. Миграция: `create_settings_table`

**Файл**: `database/migrations/2026_04_01_000001_create_settings_table.php`

```sql
CREATE TABLE settings (
    id           BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `key`        VARCHAR(191) NOT NULL UNIQUE,
    value        TEXT NULL,
    `group`      VARCHAR(50) NOT NULL DEFAULT 'general',
    created_at   TIMESTAMP NULL,
    updated_at   TIMESTAMP NULL,

    INDEX idx_settings_group (`group`)
);
```

Schema-код:
```php
Schema::create('settings', function (Blueprint $table) {
    $table->id();
    $table->string('key', 191)->unique();
    $table->text('value')->nullable();
    $table->string('group', 50)->default('general')->index();
    $table->timestamps();
});
```

### 1.2. Модель: Setting

**Файл**: `app/Models/Setting.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    // --- Whitelist допустимых ключей ---
    public const ALLOWED_KEYS = [
        'contacts.address',
        'contacts.phone',
        'contacts.email',
        'contacts.map_id',
        'schedule.weekdays',
        'schedule.saturday',
        'schedule.sunday',
        'schedule.note',
        'modals.about',          // HTML
        'modals.location_address',
        'about.history',         // HTML (Фаза 6)
        'about.mission',         // HTML (Фаза 6)
        'home.building_image',   // путь к изображению (Фаза 6)
        'seo.analytics_yandex',  // ID Яндекс.Метрики (Фаза SEO)
        'seo.analytics_google',  // ID Google Analytics (Фаза SEO)
        'seo.robots_txt',        // содержимое robots.txt (Фаза SEO)
    ];

    /**
     * Получить значение настройки по ключу.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::cached()[$key] ?? $default;
    }

    /**
     * Установить значение настройки.
     */
    public static function set(string $key, ?string $value): void
    {
        if (!in_array($key, static::ALLOWED_KEYS)) {
            throw new \InvalidArgumentException("Недопустимый ключ настройки: {$key}");
        }
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget('settings');
    }

    /**
     * Получить все настройки группы.
     *
     * @return array<string, string|null>
     */
    public static function getGroup(string $group): array
    {
        $all = static::cached();
        $prefix = $group . '.';
        $result = [];
        foreach ($all as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $result[$k] = $v;
            }
        }
        return $result;
    }

    /**
     * Все настройки из кеша (key => value).
     *
     * @return array<string, string|null>
     */
    public static function cached(): array
    {
        return Cache::rememberForever('settings', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}
```

### 1.3. Контроллер: SettingsController

**Файл**: `app/Http/Controllers/Admin/SettingsController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingsController extends Controller
{
    /**
     * GET /admin/settings
     * Форма редактирования настроек с группировкой по табам.
     */
    public function edit(): View
    {
        $settings = Setting::cached();
        return view('admin.settings.edit', compact('settings'));
    }

    /**
     * PUT /admin/settings
     * Обновление настроек.
     *
     * Валидация:
     *   'settings'                     => ['required', 'array']
     *   'settings.contacts.address'    => ['nullable', 'string', 'max:500']
     *   'settings.contacts.phone'      => ['nullable', 'string', 'max:100']
     *   'settings.contacts.email'      => ['nullable', 'email', 'max:255']
     *   'settings.contacts.map_id'     => ['nullable', 'string', 'max:255', 'regex:/^[a-f0-9]+$/']
     *   'settings.schedule.weekdays'   => ['nullable', 'string', 'max:100']
     *   'settings.schedule.saturday'   => ['nullable', 'string', 'max:100']
     *   'settings.schedule.sunday'     => ['nullable', 'string', 'max:100']
     *   'settings.schedule.note'       => ['nullable', 'string', 'max:500']
     *   'settings.modals.about'        => ['nullable', 'string', 'max:5000']
     *   'settings.modals.location_address' => ['nullable', 'string', 'max:500']
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.contacts.address' => ['nullable', 'string', 'max:500'],
            'settings.contacts.phone' => ['nullable', 'string', 'max:100'],
            'settings.contacts.email' => ['nullable', 'email', 'max:255'],
            'settings.contacts.map_id' => ['nullable', 'string', 'max:255', 'regex:/^[a-f0-9]+$/'],
            'settings.schedule.weekdays' => ['nullable', 'string', 'max:100'],
            'settings.schedule.saturday' => ['nullable', 'string', 'max:100'],
            'settings.schedule.sunday' => ['nullable', 'string', 'max:100'],
            'settings.schedule.note' => ['nullable', 'string', 'max:500'],
            'settings.modals.about' => ['nullable', 'string', 'max:5000'],
            'settings.modals.location_address' => ['nullable', 'string', 'max:500'],
        ]);

        // 1. Отфильтровать только допустимые ключи
        // 2. Обновить каждый ключ
        foreach ($validated['settings'] as $key => $value) {
            // key приходит в dot-notation (contacts.address и т.д.)
            // Нужно собрать плоский массив из вложенного
        }

        // Пошаговая логика:
        $flat = [];
        $this->flattenArray($validated['settings'], '', $flat);

        foreach ($flat as $key => $value) {
            if (in_array($key, Setting::ALLOWED_KEYS, true)) {
                Setting::set($key, $value);
            }
        }

        return redirect()->route('admin.settings.edit')
            ->with('success', 'Настройки сохранены');
    }

    /**
     * Рекурсивное преобразование вложенного массива в плоский с dot-notation ключами.
     */
    private function flattenArray(array $array, string $prefix, array &$result): void
    {
        foreach ($array as $key => $value) {
            $fullKey = $prefix ? $prefix . '.' . $key : $key;
            if (is_array($value)) {
                $this->flattenArray($value, $fullKey, $result);
            } else {
                $result[$fullKey] = $value;
            }
        }
    }
}
```

### 1.4. Роуты (admin.php)

Добавить в группу `Route::middleware('auth')`:

```php
Route::get('/settings', [SettingsController::class, 'edit'])->name('settings.edit');
Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update');
```

### 1.5. Layouts

#### admin-auth.blade.php (новый)

**Файл**: `resources/views/layouts/admin-auth.blade.php`

Назначение: центрированная карточка для login/register/verify (текущий admin.blade.php).

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Вход') -- Музей "Иркутское юнкерское училище"</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
</head>
<body class="admin-body admin-body--auth">
    <div class="admin-container admin-container--auth">
        @yield('content')
    </div>
    @stack('scripts')
</body>
</html>
```

Переменные: нет.

#### admin.blade.php (переписать)

**Файл**: `resources/views/layouts/admin.blade.php`

Назначение: панель с sidebar + main content area.

```blade
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Админ-панель') -- Музей "Иркутское юнкерское училище"</title>
    <link rel="stylesheet" href="{{ asset('css/admin.css') }}">
    @stack('styles')
</head>
<body class="admin-body admin-body--panel">
    <aside class="admin-sidebar">
        <div class="admin-sidebar__header">
            <a href="{{ route('admin.dashboard') }}" class="admin-sidebar__logo">
                Музей ИЮУ
            </a>
        </div>
        <nav class="admin-sidebar__nav">
            <a href="{{ route('admin.dashboard') }}"
               class="admin-sidebar__link {{ request()->routeIs('admin.dashboard') ? 'admin-sidebar__link--active' : '' }}">
                Дашборд
            </a>
            {{-- Ссылки добавляются по мере реализации фаз --}}
            {{-- Фаза 1: --}}
            <a href="{{ route('admin.settings.edit') }}"
               class="admin-sidebar__link {{ request()->routeIs('admin.settings.*') ? 'admin-sidebar__link--active' : '' }}">
                Настройки
            </a>
            {{-- Фаза 2: --}}
            {{-- <a href="{{ route('admin.news.index') }}" class="admin-sidebar__link">Новости</a> --}}
            {{-- Фаза 3: --}}
            {{-- <a href="{{ route('admin.excursions.index') }}" class="admin-sidebar__link">Экскурсии</a> --}}
            {{-- Фаза 4: --}}
            {{-- <a href="{{ route('admin.articles.index') }}" class="admin-sidebar__link">Статьи</a> --}}
            {{-- Фаза 5: --}}
            {{-- <a href="{{ route('admin.catalog.index', 'exposition') }}" class="admin-sidebar__link">Экспозиция</a> --}}
            {{-- <a href="{{ route('admin.catalog.index', 'archive') }}" class="admin-sidebar__link">Архив</a> --}}
        </nav>
        <div class="admin-sidebar__footer">
            <span class="admin-sidebar__user">{{ Auth::user()->name }}</span>
            <form method="POST" action="{{ route('admin.logout') }}">
                @csrf
                <button type="submit" class="admin-sidebar__logout">Выйти</button>
            </form>
        </div>
    </aside>
    <main class="admin-main">
        <div class="admin-page-header">
            <h1 class="admin-page-header__title">@yield('title', 'Админ-панель')</h1>
        </div>
        @if (session('success'))
            <div class="admin-alert admin-alert--success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="admin-alert admin-alert--error">{{ session('error') }}</div>
        @endif
        <div class="admin-content">
            @yield('content')
        </div>
    </main>
    @stack('scripts')
</body>
</html>
```

Переменные: `Auth::user()` (автоматически через auth middleware).

### 1.6. Views

#### admin/settings/edit.blade.php

**Файл**: `resources/views/admin/settings/edit.blade.php`

Extends: `layouts.admin`.
Переменные: `$settings` (array key=>value).

```blade
@extends('layouts.admin')
@section('title', 'Настройки сайта')
@section('content')
<form method="POST" action="{{ route('admin.settings.update') }}" class="admin-form">
    @csrf
    @method('PUT')

    {{-- Табы: Контакты | Расписание | Модалки --}}
    <div class="admin-tabs">
        <button type="button" class="admin-tabs__btn admin-tabs__btn--active" data-tab="contacts">Контакты</button>
        <button type="button" class="admin-tabs__btn" data-tab="schedule">Расписание</button>
        <button type="button" class="admin-tabs__btn" data-tab="modals">Модалки</button>
    </div>

    {{-- Таб: Контакты --}}
    <div class="admin-tabs__panel admin-tabs__panel--active" data-panel="contacts">
        <div class="admin-form__group">
            <label class="admin-form__label" for="contacts_address">Адрес</label>
            <input type="text" name="settings[contacts][address]" id="contacts_address"
                   value="{{ old('settings.contacts.address', $settings['contacts.address'] ?? '') }}"
                   class="admin-form__input" maxlength="500">
            @error('settings.contacts.address')
                <span class="admin-form__error">{{ $message }}</span>
            @enderror
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="contacts_phone">Телефон</label>
            <input type="text" name="settings[contacts][phone]" id="contacts_phone"
                   value="{{ old('settings.contacts.phone', $settings['contacts.phone'] ?? '') }}"
                   class="admin-form__input" maxlength="100">
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="contacts_email">Email</label>
            <input type="email" name="settings[contacts][email]" id="contacts_email"
                   value="{{ old('settings.contacts.email', $settings['contacts.email'] ?? '') }}"
                   class="admin-form__input" maxlength="255">
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="contacts_map_id">ID карты Яндекс.Конструктора</label>
            <input type="text" name="settings[contacts][map_id]" id="contacts_map_id"
                   value="{{ old('settings.contacts.map_id', $settings['contacts.map_id'] ?? '') }}"
                   class="admin-form__input" maxlength="255"
                   placeholder="Только hex-идентификатор, без URL">
            <small class="admin-form__hint">Пример: 0882d0472dd33f77a1ebbf43d7c3768c4479d7029e56e57ce49536c1530ff6df</small>
        </div>
    </div>

    {{-- Таб: Расписание --}}
    <div class="admin-tabs__panel" data-panel="schedule">
        <div class="admin-form__group">
            <label class="admin-form__label" for="schedule_weekdays">Пн-Пт</label>
            <input type="text" name="settings[schedule][weekdays]" id="schedule_weekdays"
                   value="{{ old('settings.schedule.weekdays', $settings['schedule.weekdays'] ?? '') }}"
                   class="admin-form__input" maxlength="100" placeholder="09:00 -- 17:00">
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="schedule_saturday">Суббота</label>
            <input type="text" name="settings[schedule][saturday]" id="schedule_saturday"
                   value="{{ old('settings.schedule.saturday', $settings['schedule.saturday'] ?? '') }}"
                   class="admin-form__input" maxlength="100" placeholder="10:00 -- 15:00">
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="schedule_sunday">Воскресенье</label>
            <input type="text" name="settings[schedule][sunday]" id="schedule_sunday"
                   value="{{ old('settings.schedule.sunday', $settings['schedule.sunday'] ?? '') }}"
                   class="admin-form__input" maxlength="100" placeholder="Выходной">
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="schedule_note">Примечание</label>
            <textarea name="settings[schedule][note]" id="schedule_note"
                      class="admin-form__textarea" maxlength="500" rows="3">{{ old('settings.schedule.note', $settings['schedule.note'] ?? '') }}</textarea>
        </div>
    </div>

    {{-- Таб: Модалки --}}
    <div class="admin-tabs__panel" data-panel="modals">
        <div class="admin-form__group">
            <label class="admin-form__label" for="modals_about">О музее (HTML)</label>
            <textarea name="settings[modals][about]" id="modals_about"
                      class="admin-form__textarea" rows="8" maxlength="5000">{{ old('settings.modals.about', $settings['modals.about'] ?? '') }}</textarea>
            <small class="admin-form__hint">Допускается HTML: p, strong, em, a, ul, li</small>
        </div>
        <div class="admin-form__group">
            <label class="admin-form__label" for="modals_location_address">Адрес (модалка "Как нас найти")</label>
            <input type="text" name="settings[modals][location_address]" id="modals_location_address"
                   value="{{ old('settings.modals.location_address', $settings['modals.location_address'] ?? '') }}"
                   class="admin-form__input" maxlength="500">
        </div>
    </div>

    <div class="admin-form__actions">
        <button type="submit" class="admin-form__button">Сохранить</button>
    </div>
</form>
@endsection

@push('scripts')
<script>
// Переключение табов
document.querySelectorAll('.admin-tabs__btn').forEach(btn => {
    btn.addEventListener('click', () => {
        document.querySelectorAll('.admin-tabs__btn').forEach(b => b.classList.remove('admin-tabs__btn--active'));
        document.querySelectorAll('.admin-tabs__panel').forEach(p => p.classList.remove('admin-tabs__panel--active'));
        btn.classList.add('admin-tabs__btn--active');
        document.querySelector(`[data-panel="${btn.dataset.tab}"]`).classList.add('admin-tabs__panel--active');
    });
});
</script>
@endpush
```

#### admin/dashboard.blade.php (переписать)

**Файл**: `resources/views/admin/dashboard.blade.php`

Extends: `layouts.admin`.
Переменные: нет (счетчики добавятся в Фазе 7).

```blade
@extends('layouts.admin')
@section('title', 'Дашборд')
@section('content')
<div class="admin-dashboard">
    <div class="admin-dashboard__cards">
        <div class="admin-card">
            <div class="admin-card__title">Быстрые действия</div>
            <div class="admin-card__body">
                <a href="{{ route('admin.settings.edit') }}" class="admin-card__link">Настройки сайта</a>
                {{-- Ссылки добавляются по мере фаз --}}
            </div>
        </div>
    </div>
</div>
@endsection
```

### 1.7. View Composer (AppServiceProvider)

**Файл**: `app/Providers/AppServiceProvider.php`

```php
namespace App\Providers;

use App\Models\Setting;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Загружать настройки только для публичного layout
        // Не грузятся для admin-запросов и artisan-команд
        View::composer('layouts.app', function ($view) {
            $view->with('settings', Setting::cached());
        });
    }
}
```

### 1.8. Публичные шаблоны (модификация)

#### components/modals.blade.php

Переменные: `$settings` (из View Composer через layouts.app).

```blade
<div class="modal-overlay" id="modalOverlay">
    <div class="modal" id="modal">
        <button class="modal__close" id="modalClose" aria-label="Закрыть">&times;</button>
        <h3 class="modal__title" id="modalTitle"></h3>
        <div class="modal__content" id="modalContent"></div>
    </div>
</div>

<template id="tpl-about">
    <h4>О музее</h4>
    {!! $settings['modals.about'] ?? '<p>Информация о музее</p>' !!}
</template>

<template id="tpl-schedule">
    <h4>Режим работы</h4>
    <p><strong>Понедельник -- Пятница:</strong> {{ $settings['schedule.weekdays'] ?? '09:00 -- 17:00' }}</p>
    <p><strong>Суббота:</strong> {{ $settings['schedule.saturday'] ?? '10:00 -- 15:00' }}</p>
    <p><strong>Воскресенье:</strong> {{ $settings['schedule.sunday'] ?? 'выходной' }}</p>
    @if ($settings['schedule.note'] ?? null)
        <p><em>{{ $settings['schedule.note'] }}</em></p>
    @endif
</template>

<template id="tpl-location">
    <h4>Как нас найти</h4>
    <p><strong>Адрес:</strong> {{ $settings['modals.location_address'] ?? 'г. Иркутск' }}</p>
    <div class="modal__map-placeholder">
        @if ($mapId = $settings['contacts.map_id'] ?? null)
            <script type="text/javascript" charset="utf-8" async
                src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A{{ $mapId }}&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true">
            </script>
        @else
            <p>Карта не настроена</p>
        @endif
    </div>
</template>

<template id="tpl-contacts">
    <h4>Контакты</h4>
    <p><strong>Телефон:</strong> {{ $settings['contacts.phone'] ?? 'Не указан' }}</p>
    <p><strong>Email:</strong> {{ $settings['contacts.email'] ?? 'Не указан' }}</p>
    <p><strong>Адрес:</strong> {{ $settings['contacts.address'] ?? 'Не указан' }}</p>
</template>
```

#### pages/contacts.blade.php

```blade
@extends('layouts.app')
@section('title', 'Контакты -- Музей "Иркутское юнкерское училище"')
@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Контакты', 'url' => null],
    ]" />
    <h2 class="page__title">Контакты</h2>
    <div class="contact-info">
        <h3>Контактная информация</h3>
        <p><strong>Адрес:</strong> {{ $settings['contacts.address'] ?? 'Не указан' }}</p>
        <p><strong>Телефон:</strong> {{ $settings['contacts.phone'] ?? 'Не указан' }}</p>
        <p><strong>Email:</strong> {{ $settings['contacts.email'] ?? 'Не указан' }}</p>
        <h3 style="margin-top:20px">Режим работы</h3>
        <p>Понедельник -- Пятница: {{ $settings['schedule.weekdays'] ?? '09:00 -- 17:00' }}</p>
        <p>Суббота: {{ $settings['schedule.saturday'] ?? '10:00 -- 15:00' }}</p>
        <p>Воскресенье: {{ $settings['schedule.sunday'] ?? 'Выходной' }}</p>
        @if ($settings['schedule.note'] ?? null)
            <p><em>{{ $settings['schedule.note'] }}</em></p>
        @endif
    </div>
    <div class="contact-map">
        @if ($mapId = $settings['contacts.map_id'] ?? null)
            <script type="text/javascript" charset="utf-8" async
                src="https://api-maps.yandex.ru/services/constructor/1.0/js/?um=constructor%3A{{ $mapId }}&amp;width=100%25&amp;height=720&amp;lang=ru_RU&amp;scroll=true">
            </script>
        @else
            <p>Карта не настроена</p>
        @endif
    </div>
</div>
@endsection
```

#### pages/about.blade.php

Расписание в таблице заменить на данные из `$settings`:

```blade
{{-- В секции "Режим работы" --}}
<tbody>
    <tr><td>Понедельник</td><td rowspan="5">{{ $settings['schedule.weekdays'] ?? '09:00 -- 17:00' }}</td></tr>
    <tr><td>Вторник</td></tr>
    <tr><td>Среда</td></tr>
    <tr><td>Четверг</td></tr>
    <tr><td>Пятница</td></tr>
    <tr><td>Суббота</td><td>{{ $settings['schedule.saturday'] ?? '10:00 -- 15:00' }}</td></tr>
    <tr><td>Воскресенье</td><td>{{ $settings['schedule.sunday'] ?? 'Выходной' }}</td></tr>
</tbody>
```

### 1.9. Auth views (переключить layout)

Файлы: `admin/login.blade.php`, `admin/register.blade.php`, `admin/verify.blade.php`

Замена: `@extends('layouts.admin')` -> `@extends('layouts.admin-auth')`.

### 1.10. Seeder: SettingsSeeder

**Файл**: `database/seeders/SettingsSeeder.php`

```php
namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SettingsSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function () {
            $settings = [
                ['key' => 'contacts.address', 'value' => 'г. Иркутск, ул. Ярослава Гашека, д. 5', 'group' => 'contacts'],
                ['key' => 'contacts.phone', 'value' => '+7 (3952) XX-XX-XX', 'group' => 'contacts'],
                ['key' => 'contacts.email', 'value' => 'museum@example.ru', 'group' => 'contacts'],
                ['key' => 'contacts.map_id', 'value' => '0882d0472dd33f77a1ebbf43d7c3768c4479d7029e56e57ce49536c1530ff6df', 'group' => 'contacts'],
                ['key' => 'schedule.weekdays', 'value' => '09:00 -- 17:00', 'group' => 'schedule'],
                ['key' => 'schedule.saturday', 'value' => '10:00 -- 15:00', 'group' => 'schedule'],
                ['key' => 'schedule.sunday', 'value' => 'Выходной', 'group' => 'schedule'],
                ['key' => 'schedule.note', 'value' => 'Экскурсии проводятся по предварительной записи.', 'group' => 'schedule'],
                ['key' => 'modals.about', 'value' => '<h4>О музее</h4><p>Музей "Иркутское юнкерское училище" -- внештатное музейное образование, осуществляющее свою деятельность в здании бывшего юнкерского училища.</p><p>Музей рассказывает о богатой истории военного образования в Иркутске, начиная с 1874 года и до наших дней. В экспозиции представлены уникальные документы, фотографии, предметы быта и вооружения различных исторических эпох.</p>', 'group' => 'modals'],
                ['key' => 'modals.location_address', 'value' => 'г. Иркутск, ул. Советская, д. 176', 'group' => 'modals'],
                ['key' => 'about.history', 'value' => null, 'group' => 'about'],
                ['key' => 'about.mission', 'value' => null, 'group' => 'about'],
                ['key' => 'home.building_image', 'value' => null, 'group' => 'home'],
                ['key' => 'seo.analytics_yandex', 'value' => null, 'group' => 'seo'],
                ['key' => 'seo.analytics_google', 'value' => null, 'group' => 'seo'],
                ['key' => 'seo.robots_txt', 'value' => null, 'group' => 'seo'],
            ];

            foreach ($settings as $row) {
                Setting::updateOrCreate(
                    ['key' => $row['key']],
                    ['value' => $row['value'], 'group' => $row['group']]
                );
            }
        });
    }
}
```

### 1.11. CSS: admin.css (новые BEM-блоки)

Добавить в конец `public/css/admin.css`:

```css
/* ============================================================
   Admin Panel Layout
   ============================================================ */

/* Модификатор body для auth-страниц (старое поведение) */
.admin-body--auth {
  display: flex;
  align-items: center;
  justify-content: center;
}

.admin-container--auth {
  max-width: 440px;
}

/* Модификатор body для панели */
.admin-body--panel {
  display: flex;
  align-items: stretch;
  justify-content: flex-start;
  padding: 0;
  min-height: 100vh;
}

/* --- Sidebar --- */
.admin-sidebar {
  width: 260px;
  min-height: 100vh;
  background-color: var(--color-primary);
  color: var(--color-white);
  display: flex;
  flex-direction: column;
  flex-shrink: 0;
  position: fixed;
  left: 0;
  top: 0;
  bottom: 0;
  z-index: 100;
}

.admin-sidebar__header {
  padding: 24px 20px;
  border-bottom: 1px solid rgba(255, 255, 255, 0.15);
}

.admin-sidebar__logo {
  color: var(--color-white);
  text-decoration: none;
  font-size: 1.1rem;
  font-weight: 700;
}

.admin-sidebar__nav {
  flex: 1;
  padding: 12px 0;
  display: flex;
  flex-direction: column;
}

.admin-sidebar__link {
  display: block;
  padding: 10px 20px;
  color: rgba(255, 255, 255, 0.8);
  text-decoration: none;
  font-size: 0.95rem;
  transition: background-color var(--transition), color var(--transition);
}

.admin-sidebar__link:hover {
  background-color: rgba(255, 255, 255, 0.1);
  color: var(--color-white);
}

.admin-sidebar__link--active {
  background-color: rgba(255, 255, 255, 0.15);
  color: var(--color-white);
  font-weight: 600;
}

.admin-sidebar__footer {
  padding: 16px 20px;
  border-top: 1px solid rgba(255, 255, 255, 0.15);
}

.admin-sidebar__user {
  display: block;
  font-size: 0.85rem;
  margin-bottom: 8px;
  opacity: 0.8;
}

.admin-sidebar__logout {
  background: none;
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: var(--color-white);
  padding: 6px 16px;
  border-radius: var(--radius-sm);
  cursor: pointer;
  font-family: inherit;
  font-size: 0.85rem;
  transition: background-color var(--transition);
}

.admin-sidebar__logout:hover {
  background-color: rgba(255, 255, 255, 0.1);
}

/* --- Main content area --- */
.admin-main {
  flex: 1;
  margin-left: 260px;
  padding: 0;
  min-height: 100vh;
  background-color: var(--color-bg);
}

.admin-page-header {
  padding: 24px 32px;
  background-color: var(--color-white);
  border-bottom: 1px solid var(--color-bg);
  box-shadow: var(--shadow-sm);
}

.admin-page-header__title {
  font-size: 1.4rem;
  color: var(--color-primary);
  font-weight: 700;
  margin: 0;
}

.admin-content {
  padding: 24px 32px;
}

/* --- Alerts --- */
.admin-alert {
  padding: 12px 16px;
  border-radius: var(--radius-sm);
  font-size: 0.88rem;
  margin: 16px 32px 0;
  line-height: 1.4;
}

.admin-alert--success {
  background-color: #e8f5e9;
  color: #2e7d32;
  border: 1px solid #a5d6a7;
}

.admin-alert--error {
  background-color: #fce4e4;
  color: var(--color-primary);
  border: 1px solid #e8b4b4;
}

/* --- Form --- */
.admin-form__group {
  margin-bottom: 18px;
}

.admin-form__label {
  display: block;
  font-size: 0.9rem;
  color: var(--color-text);
  margin-bottom: 6px;
  font-weight: 600;
}

.admin-form__input,
.admin-form__textarea,
.admin-form__select {
  width: 100%;
  padding: 10px 14px;
  font-size: 1rem;
  font-family: inherit;
  color: var(--color-text);
  background-color: var(--color-bg-light);
  border: 2px solid transparent;
  border-radius: var(--radius-sm);
  transition: border-color var(--transition), box-shadow var(--transition);
  outline: none;
}

.admin-form__input:focus,
.admin-form__textarea:focus,
.admin-form__select:focus {
  border-color: var(--color-primary);
  box-shadow: 0 0 0 3px rgba(139, 26, 26, 0.15);
}

.admin-form__textarea {
  resize: vertical;
  min-height: 80px;
}

.admin-form__error {
  display: block;
  font-size: 0.82rem;
  color: var(--color-accent);
  margin-top: 4px;
}

.admin-form__hint {
  display: block;
  font-size: 0.82rem;
  color: var(--color-text-light);
  margin-top: 4px;
}

.admin-form__checkbox {
  display: flex;
  align-items: center;
  gap: 8px;
}

.admin-form__checkbox input[type="checkbox"] {
  width: 18px;
  height: 18px;
  accent-color: var(--color-primary);
}

.admin-form__actions {
  margin-top: 24px;
  padding-top: 16px;
  border-top: 1px solid var(--color-bg);
}

.admin-form__button {
  padding: 10px 28px;
  font-size: 1rem;
  font-family: inherit;
  font-weight: 700;
  color: var(--color-white);
  background-color: var(--color-primary);
  border: none;
  border-radius: var(--radius-sm);
  cursor: pointer;
  transition: background-color var(--transition);
}

.admin-form__button:hover {
  background-color: var(--color-primary-dark);
}

.admin-form__button--secondary {
  background-color: var(--color-border);
}

.admin-form__button--secondary:hover {
  background-color: var(--color-primary);
}

.admin-form__button--danger {
  background-color: #c62828;
}

.admin-form__button--danger:hover {
  background-color: #b71c1c;
}

/* Предпросмотр изображения */
.admin-form__image-preview {
  max-width: 300px;
  max-height: 200px;
  border-radius: var(--radius-sm);
  margin-top: 8px;
  border: 1px solid var(--color-bg);
}

/* --- Tabs --- */
.admin-tabs {
  display: flex;
  gap: 4px;
  margin-bottom: 24px;
  border-bottom: 2px solid var(--color-bg);
}

.admin-tabs__btn {
  padding: 10px 20px;
  font-size: 0.95rem;
  font-family: inherit;
  font-weight: 600;
  background: none;
  border: none;
  border-bottom: 2px solid transparent;
  margin-bottom: -2px;
  cursor: pointer;
  color: var(--color-text-light);
  transition: color var(--transition), border-color var(--transition);
}

.admin-tabs__btn:hover {
  color: var(--color-primary);
}

.admin-tabs__btn--active {
  color: var(--color-primary);
  border-bottom-color: var(--color-primary);
}

.admin-tabs__panel {
  display: none;
}

.admin-tabs__panel--active {
  display: block;
}

/* --- Table --- */
.admin-table {
  width: 100%;
  border-collapse: collapse;
  background-color: var(--color-white);
  border-radius: var(--radius-md);
  overflow: hidden;
  box-shadow: var(--shadow-sm);
}

.admin-table th,
.admin-table td {
  padding: 12px 16px;
  text-align: left;
  font-size: 0.92rem;
  border-bottom: 1px solid var(--color-bg);
}

.admin-table th {
  background-color: var(--color-bg-light);
  font-weight: 700;
  color: var(--color-text);
}

.admin-table td {
  color: var(--color-text-light);
}

.admin-table__actions {
  display: flex;
  gap: 8px;
  align-items: center;
}

.admin-table__link {
  color: var(--color-primary);
  text-decoration: none;
  font-size: 0.85rem;
  font-weight: 600;
}

.admin-table__link:hover {
  color: var(--color-accent);
}

.admin-table__link--danger {
  color: #c62828;
}

.admin-table__link--danger:hover {
  color: #b71c1c;
}

/* --- Card (для dashboard) --- */
.admin-card {
  background-color: var(--color-white);
  border-radius: var(--radius-md);
  box-shadow: var(--shadow-sm);
  padding: 24px;
  margin-bottom: 16px;
}

.admin-card__title {
  font-size: 1.05rem;
  font-weight: 700;
  color: var(--color-text);
  margin-bottom: 16px;
}

.admin-card__link {
  display: block;
  padding: 8px 0;
  color: var(--color-primary);
  text-decoration: none;
  font-size: 0.95rem;
}

.admin-card__link:hover {
  color: var(--color-accent);
}

/* --- Dashboard --- */
.admin-dashboard__cards {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
  gap: 16px;
}

/* --- Pagination (для таблиц) --- */
.admin-pagination {
  display: flex;
  justify-content: center;
  gap: 4px;
  margin-top: 24px;
}

/* --- Адаптивность панели --- */
@media (max-width: 768px) {
  .admin-sidebar {
    width: 100%;
    position: relative;
    min-height: auto;
  }

  .admin-main {
    margin-left: 0;
  }

  .admin-body--panel {
    flex-direction: column;
  }

  .admin-content {
    padding: 16px;
  }

  .admin-page-header {
    padding: 16px;
  }
}
```

### 1.12. Тесты: Фаза 1

**Файл**: `tests/Feature/Admin/SettingsTest.php`

```php
namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettingsTest extends TestCase
{
    use RefreshDatabase;

    // --- Auth redirect ---
    public function test_settings_edit_redirects_guests(): void
    // GET /admin/settings -> 302 -> /admin/login

    public function test_settings_update_redirects_guests(): void
    // PUT /admin/settings -> 302 -> /admin/login

    // --- CRUD ---
    public function test_authenticated_user_can_view_settings(): void
    // GET /admin/settings -> 200

    public function test_authenticated_user_can_update_settings(): void
    // PUT /admin/settings с валидными данными -> 302 + session('success')
    // assertDatabaseHas('settings', ['key' => 'contacts.phone', 'value' => '...'])

    // --- Валидация ---
    public function test_settings_update_validates_email(): void
    // PUT с невалидным email -> session errors

    public function test_settings_update_validates_map_id_hex(): void
    // PUT с невалидным map_id (спецсимволы) -> session errors

    public function test_settings_rejects_unknown_keys(): void
    // PUT с key не из ALLOWED_KEYS -> ключ не сохранён в БД

    // --- Model ---
    public function test_setting_get_returns_default_for_missing_key(): void
    // Setting::get('nonexistent', 'default') === 'default'

    public function test_setting_cache_invalidates_on_set(): void
    // Setting::set() -> Cache::forget -> повторный get возвращает новое значение
}
```

---

## Фаза 2: Новости (CRUD + загрузка изображений + санитизация)

**Цель**: CRUD новостей с изображениями. Подключение Purify для HTML-санитизации. Создание переиспользуемого ImageUploadService.

---

### 2.1. Зависимость

```bash
composer require stevebauman/purify
php artisan vendor:publish --provider="Stevebauman\Purify\PurifyServiceProvider"
```

Конфигурация `config/purify.php` -- см. "Общие сквозные правила" выше.

### 2.2. Миграция: `create_news_table`

**Файл**: `database/migrations/2026_04_01_000002_create_news_table.php`

```sql
CREATE TABLE news (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    text          TEXT NOT NULL,
    image_path    VARCHAR(255) NULL,
    published_at  DATE NOT NULL,
    is_published  BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,
    deleted_at    TIMESTAMP NULL,

    INDEX idx_news_published (is_published, published_at DESC)
);
```

Schema-код:
```php
Schema::create('news', function (Blueprint $table) {
    $table->id();
    $table->string('title', 255);
    $table->text('text');
    $table->string('image_path', 255)->nullable();
    $table->date('published_at')->index();
    $table->boolean('is_published')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_published', 'published_at']);
});
```

### 2.3. Модель: News

**Файл**: `app/Models/News.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'text',
        'image_path',
        'published_at',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    // --- Scopes ---

    /**
     * Только опубликованные новости.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    // --- Accessors ---

    /**
     * URL изображения (через storage symlink).
     */
    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    /**
     * Дата в формате "15.02.2026".
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->published_at->format('d.m.Y');
    }

    // --- Boot events ---

    protected static function booted(): void
    {
        // Удаление изображения ТОЛЬКО при forceDelete
        static::forceDeleting(function (News $news) {
            if ($news->image_path) {
                app(\App\Services\ImageUploadService::class)->delete($news->image_path);
            }
        });
    }
}
```

### 2.4. Сервис: ImageUploadService

**Файл**: `app/Services/ImageUploadService.php`

```php
namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageUploadService
{
    /**
     * Загрузить изображение в storage/app/public/uploads/{directory}/.
     *
     * @param UploadedFile $file      Загруженный файл
     * @param string       $directory Поддиректория (news, excursions, content и т.д.)
     * @return string                  Относительный путь от storage/app/public/ (uploads/news/uuid.jpg)
     */
    public function upload(UploadedFile $file, string $directory): string
    {
        $filename = Str::uuid() . '.' . $file->extension();
        $path = 'uploads/' . $directory;
        $file->storeAs($path, $filename, 'public');
        return $path . '/' . $filename;
    }

    /**
     * Удалить файл из storage.
     *
     * @param string|null $path Относительный путь от storage/app/public/
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * Заменить файл: удалить старый, загрузить новый.
     *
     * @return string Путь нового файла
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $this->delete($oldPath);
        return $this->upload($file, $directory);
    }
}
```

### 2.5. Контроллер: NewsController (Admin)

**Файл**: `app/Http/Controllers/Admin/NewsController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class NewsController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * GET /admin/news
     * Список новостей (с пагинацией, последние сверху).
     */
    public function index(): View
    {
        $news = News::orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('admin.news.index', compact('news'));
    }

    /**
     * GET /admin/news/create
     */
    public function create(): View
    {
        return view('admin.news.create');
    }

    /**
     * POST /admin/news
     *
     * Валидация:
     *   'title'        => ['required', 'string', 'max:255']
     *   'text'         => ['required', 'string', 'max:10000']
     *   'published_at' => ['required', 'date', 'before_or_equal:today']
     *   'image'        => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
     *   'is_published' => ['boolean']
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:10000'],
            'published_at' => ['required', 'date', 'before_or_equal:today'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'is_published' => ['boolean'],
        ]);

        // 1. Загрузить изображение (если есть)
        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageService->upload($request->file('image'), 'news');
        }

        // 2. Создать запись
        News::create([
            'title' => $validated['title'],
            'text' => $validated['text'],
            'published_at' => $validated['published_at'],
            'image_path' => $imagePath,
            'is_published' => $validated['is_published'] ?? false,
        ]);

        return redirect()->route('admin.news.index')
            ->with('success', 'Новость создана');
    }

    /**
     * GET /admin/news/{news}/edit
     */
    public function edit(News $news): View
    {
        return view('admin.news.edit', compact('news'));
    }

    /**
     * PUT /admin/news/{news}
     *
     * Валидация: та же что в store, image остаётся nullable.
     * Логика: если загружен новый image -> replace, если нет -> не трогать.
     */
    public function update(Request $request, News $news): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:10000'],
            'published_at' => ['required', 'date', 'before_or_equal:today'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'is_published' => ['boolean'],
        ]);

        $data = [
            'title' => $validated['title'],
            'text' => $validated['text'],
            'published_at' => $validated['published_at'],
            'is_published' => $validated['is_published'] ?? false,
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace(
                $news->image_path,
                $request->file('image'),
                'news'
            );
        }

        $news->update($data);

        return redirect()->route('admin.news.index')
            ->with('success', 'Новость обновлена');
    }

    /**
     * DELETE /admin/news/{news}
     * SoftDelete. Файл НЕ удаляется (удалится при forceDelete).
     */
    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return redirect()->route('admin.news.index')
            ->with('success', 'Новость удалена');
    }
}
```

### 2.6. Контроллер: PageController (публичная часть)

**Файл**: `app/Http/Controllers/PageController.php`

```php
namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\View\View;

class PageController extends Controller
{
    /**
     * GET /news
     * Публичная страница новостей с пагинацией.
     */
    public function news(): View
    {
        $news = News::published()
            ->orderByDesc('published_at')
            ->paginate(12);

        return view('pages.news', compact('news'));
    }
}
```

### 2.7. Роуты

#### admin.php

В группу `Route::middleware('auth')`:

```php
Route::resource('news', NewsController::class)->except(['show']);
```

#### web.php

Заменить:
```php
Route::get('/news', fn () => view('pages.news'))->name('news');
```
На:
```php
Route::get('/news', [PageController::class, 'news'])->name('news');
```

### 2.8. Views

#### admin/news/index.blade.php

Extends: `layouts.admin`.
Переменные: `$news` (LengthAwarePaginator).

```blade
@extends('layouts.admin')
@section('title', 'Новости')
@section('content')
<div style="margin-bottom:16px">
    <a href="{{ route('admin.news.create') }}" class="admin-form__button">Добавить новость</a>
</div>

<table class="admin-table">
    <thead>
        <tr>
            <th>Дата</th>
            <th>Заголовок</th>
            <th>Статус</th>
            <th>Действия</th>
        </tr>
    </thead>
    <tbody>
        @forelse ($news as $item)
        <tr>
            <td>{{ $item->formatted_date }}</td>
            <td>{{ $item->title }}</td>
            <td>{{ $item->is_published ? 'Опубликована' : 'Черновик' }}</td>
            <td class="admin-table__actions">
                <a href="{{ route('admin.news.edit', $item) }}" class="admin-table__link">Редактировать</a>
                <form method="POST" action="{{ route('admin.news.destroy', $item) }}" style="display:inline">
                    @csrf @method('DELETE')
                    <button type="submit" class="admin-table__link admin-table__link--danger"
                            onclick="return confirm('Удалить новость \'{{ addslashes($item->title) }}\'?')">
                        Удалить
                    </button>
                </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="4">Новостей нет</td></tr>
        @endforelse
    </tbody>
</table>

{{ $news->links() }}
@endsection
```

#### admin/news/create.blade.php

Extends: `layouts.admin`.
Переменные: нет.

```blade
@extends('layouts.admin')
@section('title', 'Добавить новость')
@section('content')
<form method="POST" action="{{ route('admin.news.store') }}" enctype="multipart/form-data" class="admin-form">
    @csrf
    @include('admin.news._form')
    <div class="admin-form__actions">
        <button type="submit" class="admin-form__button">Создать</button>
        <a href="{{ route('admin.news.index') }}" class="admin-form__button admin-form__button--secondary">Отмена</a>
    </div>
</form>
@endsection
```

#### admin/news/edit.blade.php

Extends: `layouts.admin`.
Переменные: `$news` (News).

```blade
@extends('layouts.admin')
@section('title', 'Редактировать новость')
@section('content')
<form method="POST" action="{{ route('admin.news.update', $news) }}" enctype="multipart/form-data" class="admin-form">
    @csrf @method('PUT')
    @include('admin.news._form', ['news' => $news])
    <div class="admin-form__actions">
        <button type="submit" class="admin-form__button">Сохранить</button>
        <a href="{{ route('admin.news.index') }}" class="admin-form__button admin-form__button--secondary">Отмена</a>
    </div>
</form>
@endsection
```

#### admin/news/_form.blade.php

Переменные: `$news` (News|null).

```blade
@php $news = $news ?? null; @endphp

<div class="admin-form__group">
    <label class="admin-form__label" for="title">Заголовок *</label>
    <input type="text" name="title" id="title" required
           value="{{ old('title', $news?->title) }}"
           class="admin-form__input" maxlength="255">
    @error('title') <span class="admin-form__error">{{ $message }}</span> @enderror
</div>

<div class="admin-form__group">
    <label class="admin-form__label" for="published_at">Дата публикации *</label>
    <input type="date" name="published_at" id="published_at" required
           value="{{ old('published_at', $news?->published_at?->format('Y-m-d') ?? now()->format('Y-m-d')) }}"
           class="admin-form__input" max="{{ now()->format('Y-m-d') }}">
    @error('published_at') <span class="admin-form__error">{{ $message }}</span> @enderror
</div>

<div class="admin-form__group">
    <label class="admin-form__label" for="text">Текст *</label>
    <textarea name="text" id="text" required rows="6"
              class="admin-form__textarea" maxlength="10000">{{ old('text', $news?->text) }}</textarea>
    @error('text') <span class="admin-form__error">{{ $message }}</span> @enderror
</div>

<div class="admin-form__group">
    <label class="admin-form__label" for="image">Изображение</label>
    @if ($news?->image_url)
        <div><img src="{{ $news->image_url }}" alt="" class="admin-form__image-preview"></div>
    @endif
    <input type="file" name="image" id="image" accept="image/jpeg,image/png,image/webp"
           class="admin-form__input">
    <small class="admin-form__hint">JPEG, PNG или WebP. Максимум 5 МБ.</small>
    @error('image') <span class="admin-form__error">{{ $message }}</span> @enderror
</div>

<div class="admin-form__group">
    <label class="admin-form__checkbox">
        <input type="hidden" name="is_published" value="0">
        <input type="checkbox" name="is_published" value="1"
               {{ old('is_published', $news?->is_published ?? true) ? 'checked' : '' }}>
        Опубликовать
    </label>
</div>
```

#### pages/news.blade.php (публичная)

Переменные: `$news` (LengthAwarePaginator).

```blade
@extends('layouts.app')
@section('title', 'Новости -- Музей "Иркутское юнкерское училище"')
@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Новости', 'url' => null],
    ]" />
    <h2 class="page__title">Новости</h2>
    <div class="news-list">
        @forelse ($news as $item)
        <article class="news-card">
            <div class="news-card__date">{{ $item->formatted_date }}</div>
            <h3 class="news-card__title">{{ $item->title }}</h3>
            <p class="news-card__text">{{ $item->text }}</p>
            @if ($item->image_url)
                <div class="news-card__image">
                    <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
                </div>
            @endif
        </article>
        @empty
        <p>Новостей пока нет.</p>
        @endforelse
    </div>
    {{ $news->links() }}
</div>
@endsection
```

### 2.9. Seeder: NewsSeeder

**Файл**: `database/seeders/NewsSeeder.php`

10 новостей из текущего хардкода `pages/news.blade.php`. Данные:

```php
$items = [
    ['title' => 'Открытие выставки "Награды иркутских юнкеров"', 'published_at' => '2026-02-15', 'text' => '...'],
    ['title' => 'Лекция по истории топографической службы', 'published_at' => '2026-02-03', 'text' => '...'],
    // ... все 10 новостей
];

foreach ($items as $item) {
    News::updateOrCreate(
        ['title' => $item['title']],
        ['text' => $item['text'], 'published_at' => $item['published_at'], 'is_published' => true]
    );
}
```

### 2.10. Конфигурация

- `composer.json`: добавить `"stevebauman/purify": "^3.0"` в require.
- `.env.example`: без изменений.
- `php artisan storage:link` (одноразово при деплое).

### 2.11. Тесты: Фаза 2

**Файл**: `tests/Feature/Admin/NewsTest.php`

```php
class NewsTest extends TestCase
{
    use RefreshDatabase;

    // Auth
    public function test_news_index_redirects_guests(): void
    public function test_news_create_redirects_guests(): void

    // CRUD
    public function test_authenticated_user_can_view_news_index(): void
    public function test_authenticated_user_can_create_news(): void
    public function test_authenticated_user_can_update_news(): void
    public function test_authenticated_user_can_delete_news(): void
    public function test_news_is_soft_deleted(): void

    // Валидация
    public function test_news_store_requires_title(): void
    public function test_news_store_requires_text(): void
    public function test_news_store_requires_published_at(): void
    public function test_news_store_validates_image_type(): void
    public function test_news_store_validates_image_size(): void

    // Изображения
    public function test_news_store_uploads_image(): void
    public function test_news_update_replaces_image(): void
    public function test_news_force_delete_removes_image(): void
}
```

**Файл**: `tests/Feature/Public/NewsPageTest.php`

```php
class NewsPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_news_page_returns_200(): void
    public function test_news_page_shows_published_news(): void
    public function test_news_page_hides_unpublished_news(): void
    public function test_news_page_paginates(): void
}
```

---

## Фаза 3: Экскурсии (CRUD + TinyMCE)

**Цель**: CRUD экскурсий с WYSIWYG-редактором. Self-hosted TinyMCE.

---

### 3.1. Миграция: `create_excursions_table`

**Файл**: `database/migrations/2026_04_01_000003_create_excursions_table.php`

```sql
CREATE TABLE excursions (
    id                  BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug                VARCHAR(191) NOT NULL UNIQUE,
    title               VARCHAR(255) NOT NULL,
    short_title         VARCHAR(100) NULL,       -- для кнопок на главной (допускает HTML entities)
    short_description   TEXT NOT NULL,            -- plain text для страницы /excursions
    duration_minutes    SMALLINT UNSIGNED NOT NULL DEFAULT 60,
    group_size_min      SMALLINT UNSIGNED NOT NULL DEFAULT 5,
    group_size_max      SMALLINT UNSIGNED NOT NULL DEFAULT 25,
    description         TEXT NOT NULL,            -- WYSIWYG HTML
    what_you_see        TEXT NULL,                -- WYSIWYG HTML
    interesting_facts   TEXT NULL,                -- WYSIWYG HTML
    image_path          VARCHAR(255) NULL,
    is_published        BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order          INT UNSIGNED NOT NULL DEFAULT 0,
    meta_title          VARCHAR(255) NULL,
    meta_description    VARCHAR(500) NULL,
    created_at          TIMESTAMP NULL,
    updated_at          TIMESTAMP NULL,
    deleted_at          TIMESTAMP NULL,

    UNIQUE INDEX idx_excursions_slug (slug),
    INDEX idx_excursions_published (is_published, sort_order)
);
```

Schema-код:
```php
Schema::create('excursions', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 191)->unique();
    $table->string('title', 255);
    $table->string('short_title', 100)->nullable();
    $table->text('short_description');
    $table->unsignedSmallInteger('duration_minutes')->default(60);
    $table->unsignedSmallInteger('group_size_min')->default(5);
    $table->unsignedSmallInteger('group_size_max')->default(25);
    $table->text('description');
    $table->text('what_you_see')->nullable();
    $table->text('interesting_facts')->nullable();
    $table->string('image_path', 255)->nullable();
    $table->boolean('is_published')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('meta_title', 255)->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_published', 'sort_order']);
});
```

### 3.2. Модель: Excursion

**Файл**: `app/Models/Excursion.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Stevebauman\Purify\Facades\Purify;

class Excursion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'short_title',
        'short_description',
        'duration_minutes',
        'group_size_min',
        'group_size_max',
        'description',
        'what_you_see',
        'interesting_facts',
        'image_path',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'group_size_min' => 'integer',
        'group_size_max' => 'integer',
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    // --- Мутаторы (HTML-санитизация при записи) ---

    public function setDescriptionAttribute(string $value): void
    {
        $this->attributes['description'] = Purify::clean($value);
    }

    public function setWhatYouSeeAttribute(?string $value): void
    {
        $this->attributes['what_you_see'] = $value ? Purify::clean($value) : null;
    }

    public function setInterestingFactsAttribute(?string $value): void
    {
        $this->attributes['interesting_facts'] = $value ? Purify::clean($value) : null;
    }

    // --- Scopes ---

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // --- Accessors ---

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path
            ? asset('storage/' . $this->image_path)
            : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    // --- Boot ---

    protected static function booted(): void
    {
        static::forceDeleting(function (Excursion $excursion) {
            if ($excursion->image_path) {
                app(\App\Services\ImageUploadService::class)->delete($excursion->image_path);
            }
        });
    }
}
```

### 3.3. TinyMCE self-hosted

Скачать TinyMCE 6 community (MIT license) в `public/vendor/tinymce/`.

Структура:
```
public/vendor/tinymce/
  tinymce.min.js
  themes/silver/
  icons/default/
  skins/ui/oxide/
  plugins/
    autolink/
    code/
    image/
    link/
    lists/
    table/
    paste/   (теперь PowerPaste или встроенный)
```

### 3.4. Blade-partial: admin/partials/tinymce.blade.php

**Файл**: `resources/views/admin/partials/tinymce.blade.php`

Подключение TinyMCE с безопасной конфигурацией:

```blade
<script src="{{ asset('vendor/tinymce/tinymce.min.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    tinymce.init({
        selector: 'textarea.wysiwyg',
        language: 'ru',
        base_url: '{{ asset("vendor/tinymce") }}',
        license_key: 'gpl',
        plugins: 'autolink code image link lists table',
        toolbar: 'undo redo | blocks | bold italic underline | bullist numlist | link image table | code',
        menubar: false,
        statusbar: true,
        height: 400,
        valid_elements: 'p,br,strong/b,em/i,u,h2,h3,h4,ul,ol,li,a[href|target|rel],img[src|alt|width|height|loading],blockquote,table,thead,tbody,tr,td,th,figure[class],figcaption',
        invalid_elements: 'script,iframe,form,input,object,embed',
        paste_word_valid_elements: 'p,b,strong,i,em,u,h2,h3,h4,ul,ol,li,a[href],table,tr,td,th',
        relative_urls: false,
        remove_script_host: false,
        images_upload_url: '{{ route("admin.upload.image") }}',
        images_upload_handler: function (blobInfo, progress) {
            return new Promise(function (resolve, reject) {
                var formData = new FormData();
                formData.append('image', blobInfo.blob(), blobInfo.filename());

                var xhr = new XMLHttpRequest();
                xhr.open('POST', '{{ route("admin.upload.image") }}');
                xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

                xhr.upload.onprogress = function (e) {
                    progress(e.loaded / e.total * 100);
                };

                xhr.onload = function () {
                    if (xhr.status !== 200) {
                        reject('Ошибка загрузки: ' + xhr.status);
                        return;
                    }
                    var json = JSON.parse(xhr.responseText);
                    if (!json || !json.location) {
                        reject('Некорректный ответ сервера');
                        return;
                    }
                    resolve(json.location);
                };

                xhr.onerror = function () {
                    reject('Ошибка сети');
                };

                xhr.send(formData);
            });
        },
    });
});
</script>
```

### 3.5. UploadController

**Файл**: `app/Http/Controllers/Admin/UploadController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * POST /admin/upload/image
     * Загрузка изображения из TinyMCE WYSIWYG.
     *
     * Валидация:
     *   'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
     *
     * @return JsonResponse { location: "https://...url..." }
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $path = $this->imageService->upload($request->file('image'), 'content');

        return response()->json([
            'location' => asset('storage/' . $path),
        ]);
    }
}
```

### 3.6. Контроллер: ExcursionController (Admin)

**Файл**: `app/Http/Controllers/Admin/ExcursionController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Excursion;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ExcursionController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * GET /admin/excursions
     */
    public function index(): View
    {
        $excursions = Excursion::orderBy('sort_order')->paginate(20);
        return view('admin.excursions.index', compact('excursions'));
    }

    /**
     * GET /admin/excursions/create
     */
    public function create(): View
    {
        return view('admin.excursions.create');
    }

    /**
     * POST /admin/excursions
     *
     * Валидация:
     *   'slug'              => ['required', 'string', 'max:191', 'alpha_dash', 'unique:excursions,slug']
     *   'title'             => ['required', 'string', 'max:255']
     *   'short_title'       => ['nullable', 'string', 'max:100']
     *   'short_description' => ['required', 'string', 'max:2000']
     *   'duration_minutes'  => ['required', 'integer', 'min:10', 'max:480']
     *   'group_size_min'    => ['required', 'integer', 'min:1', 'max:100']
     *   'group_size_max'    => ['required', 'integer', 'min:1', 'max:200', 'gte:group_size_min']
     *   'description'       => ['required', 'string', 'max:50000']
     *   'what_you_see'      => ['nullable', 'string', 'max:50000']
     *   'interesting_facts' => ['nullable', 'string', 'max:50000']
     *   'image'             => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
     *   'is_published'      => ['boolean']
     *   'sort_order'        => ['integer', 'min:0']
     *   'meta_title'        => ['nullable', 'string', 'max:255']
     *   'meta_description'  => ['nullable', 'string', 'max:500']
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', 'unique:excursions,slug'],
            'title' => ['required', 'string', 'max:255'],
            'short_title' => ['nullable', 'string', 'max:100'],
            'short_description' => ['required', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'group_size_min' => ['required', 'integer', 'min:1', 'max:100'],
            'group_size_max' => ['required', 'integer', 'min:1', 'max:200', 'gte:group_size_min'],
            'description' => ['required', 'string', 'max:50000'],
            'what_you_see' => ['nullable', 'string', 'max:50000'],
            'interesting_facts' => ['nullable', 'string', 'max:50000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageService->upload($request->file('image'), 'excursions');
        }

        Excursion::create(array_merge(
            collect($validated)->except('image')->toArray(),
            ['image_path' => $imagePath, 'is_published' => $validated['is_published'] ?? false]
        ));

        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия создана');
    }

    /**
     * GET /admin/excursions/{excursion}/edit
     * Route model binding по slug.
     */
    public function edit(Excursion $excursion): View
    {
        return view('admin.excursions.edit', compact('excursion'));
    }

    /**
     * PUT /admin/excursions/{excursion}
     * Аналогичная валидация, slug unique кроме текущей записи.
     */
    public function update(Request $request, Excursion $excursion): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', Rule::unique('excursions', 'slug')->ignore($excursion->id)],
            'title' => ['required', 'string', 'max:255'],
            'short_title' => ['nullable', 'string', 'max:100'],
            'short_description' => ['required', 'string', 'max:2000'],
            'duration_minutes' => ['required', 'integer', 'min:10', 'max:480'],
            'group_size_min' => ['required', 'integer', 'min:1', 'max:100'],
            'group_size_max' => ['required', 'integer', 'min:1', 'max:200', 'gte:group_size_min'],
            'description' => ['required', 'string', 'max:50000'],
            'what_you_see' => ['nullable', 'string', 'max:50000'],
            'interesting_facts' => ['nullable', 'string', 'max:50000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $data = collect($validated)->except('image')->toArray();
        $data['is_published'] = $validated['is_published'] ?? false;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace(
                $excursion->image_path, $request->file('image'), 'excursions'
            );
        }

        $excursion->update($data);

        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия обновлена');
    }

    /**
     * DELETE /admin/excursions/{excursion}
     */
    public function destroy(Excursion $excursion): RedirectResponse
    {
        $excursion->delete();
        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия удалена');
    }
}
```

### 3.7. PageController (дополнение)

Добавить методы:

```php
/**
 * GET /excursions
 */
public function excursions(): View
{
    $excursions = Excursion::published()
        ->ordered()
        ->get();

    return view('pages.excursions', compact('excursions'));
}

/**
 * GET /excursion/{excursion:slug}
 */
public function excursionShow(Excursion $excursion): View
{
    abort_unless($excursion->is_published, 404);

    return view('pages.excursion-show', compact('excursion'));
}
```

### 3.8. Роуты

#### admin.php

```php
Route::resource('excursions', ExcursionController::class)->except(['show']);
Route::post('/upload/image', [UploadController::class, 'image'])->name('upload.image');
```

#### web.php

Заменить:
```php
Route::get('/excursions', fn () => view('pages.excursions'))->name('excursions');
Route::get('/excursion/overview', fn () => view('pages.excursion-overview'))->name('excursion-overview');
// ... и все 6 excursion-* роутов
```
На:
```php
Route::get('/excursions', [PageController::class, 'excursions'])->name('excursions');
Route::get('/excursion/{excursion:slug}', [PageController::class, 'excursionShow'])->name('excursion.show');

// 301 редиректы со старых URL
Route::redirect('/excursion/overview', '/excursion/overview', 301); // slug совпадает, но route name меняется
// Примечание: slug-и совпадают с текущими URL-сегментами, поэтому 301 не нужны
// если /excursion/{slug} уже покрывает все варианты.
// Но нужны именованные роуты для обратной совместимости:
// Старые именованные роуты (excursion-overview и т.д.) заменяются на excursion.show
```

### 3.9. Views

#### admin/excursions/_form.blade.php

Extends: `layouts.admin` (через create/edit).
Переменные: `$excursion` (Excursion|null).

Содержит: slug, title, short_title, short_description (textarea), duration_minutes, group_size_min, group_size_max, description (textarea.wysiwyg), what_you_see (textarea.wysiwyg), interesting_facts (textarea.wysiwyg), image (file + preview), is_published (checkbox), sort_order, meta_title, meta_description.

TinyMCE подключается через `@push('scripts') @include('admin.partials.tinymce') @endpush`.

#### pages/excursion-show.blade.php (новый)

**Файл**: `resources/views/pages/excursion-show.blade.php`

Extends: `layouts.app`.
Переменные: `$excursion` (Excursion).

```blade
@extends('layouts.app')
@section('title', ($excursion->meta_title ?: $excursion->title) . ' -- Музей "Иркутское юнкерское училище"')

@section('content')
<div class="page">
    <x-breadcrumbs :items="[
        ['title' => 'Главная', 'url' => route('home')],
        ['title' => 'Экскурсии', 'url' => route('excursions')],
        ['title' => $excursion->title, 'url' => null],
    ]" />
    <h2 class="page__title">{{ $excursion->title }}</h2>
    <div class="article">
        <p><strong>Продолжительность:</strong> {{ $excursion->duration_minutes }} минут</p>
        <p><strong>Размер группы:</strong> от {{ $excursion->group_size_min }} до {{ $excursion->group_size_max }} человек</p>

        <h3>Описание экскурсии</h3>
        {!! $excursion->description !!}

        @if ($excursion->image_url)
            <img src="{{ $excursion->image_url }}" alt="{{ $excursion->title }}" loading="lazy" style="max-width:100%;border-radius:var(--radius-md);margin:16px 0">
        @endif

        @if ($excursion->what_you_see)
            <h3>Что вы увидите</h3>
            {!! $excursion->what_you_see !!}
        @endif

        @if ($excursion->interesting_facts)
            <h3>Интересные факты</h3>
            {!! $excursion->interesting_facts !!}
        @endif

        <p style="margin-top:24px">
            <a href="{{ route('excursions') }}" style="color:#D4611E;font-weight:600">
                &larr; Вернуться к списку экскурсий
            </a>
        </p>
    </div>
</div>
@endsection
```

#### pages/excursions.blade.php (обновить)

Переменные: `$excursions` (Collection).

```blade
@forelse ($excursions as $excursion)
<div class="excursion-card">
    <div class="excursion-card__image">
        @if ($excursion->image_url)
            <img src="{{ $excursion->image_url }}" alt="{{ $excursion->title }}" loading="lazy">
        @else
            Фото
        @endif
    </div>
    <div class="excursion-card__body">
        <h3 class="excursion-card__title">{{ $excursion->title }}</h3>
        <p class="excursion-card__text">{{ $excursion->short_description }}</p>
        <a href="{{ route('excursion.show', $excursion) }}" class="excursion-card__link">Подробнее</a>
    </div>
</div>
@empty
<p>Экскурсий пока нет.</p>
@endforelse
```

#### pages/home.blade.php (обновить секцию экскурсий)

Переменные: `$excursions` (Collection, передаётся из PageController::home()).

```blade
{{-- Кнопки экскурсий из БД --}}
<div class="excursions__buttons excursions__buttons--top">
    @foreach ($excursions->take(3) as $excursion)
        <a href="{{ route('excursion.show', $excursion) }}" class="excursions__btn">
            {!! $excursion->short_title ?: e($excursion->title) !!}
        </a>
    @endforeach
</div>
{{-- ... image ... --}}
<div class="excursions__buttons excursions__buttons--bottom">
    @foreach ($excursions->skip(3)->take(3) as $excursion)
        <a href="{{ route('excursion.show', $excursion) }}" class="excursions__btn">
            {!! $excursion->short_title ?: e($excursion->title) !!}
        </a>
    @endforeach
</div>
```

### 3.10. Seeder: ExcursionsSeeder

**Файл**: `database/seeders/ExcursionsSeeder.php`

6 экскурсий из текущих шаблонов. Slug-и: `overview`, `junker`, `awards`, `topographic-service`, `irkutsk-topographic`, `documents`.

```php
$excursions = [
    [
        'slug' => 'overview',
        'title' => 'Обзорная экскурсия (по всему музею)',
        'short_title' => 'Обзорная<br>(по всему музею)',
        'short_description' => 'Знакомство со всеми экспозиционными залами музея...',
        'duration_minutes' => 60,
        'group_size_min' => 5,
        'group_size_max' => 25,
        'description' => '<p>Обзорная экскурсия по музею...</p>',
        'what_you_see' => '<p>Вы пройдёте по всем залам музея...</p>',
        'interesting_facts' => '<p>Здание музея -- один из немногих...</p>',
        'sort_order' => 1,
    ],
    // ... остальные 5
];
```

### 3.11. Удаление старых файлов

После верификации контента в БД удалить:
- `pages/excursion-overview.blade.php`
- `pages/excursion-junker.blade.php`
- `pages/excursion-awards.blade.php`
- `pages/excursion-topographic-service.blade.php`
- `pages/excursion-irkutsk-topographic.blade.php`
- `pages/excursion-documents.blade.php`

### 3.12. Тесты: Фаза 3

**Файл**: `tests/Feature/Admin/ExcursionTest.php`

```php
class ExcursionTest extends TestCase
{
    use RefreshDatabase;

    public function test_excursions_index_redirects_guests(): void
    public function test_authenticated_user_can_create_excursion(): void
    public function test_authenticated_user_can_update_excursion(): void
    public function test_authenticated_user_can_delete_excursion(): void
    public function test_excursion_store_requires_slug(): void
    public function test_excursion_store_requires_unique_slug(): void
    public function test_excursion_group_size_max_gte_min(): void
    public function test_excursion_description_sanitized(): void
    // Вставить <script>alert(1)</script> в description -> не сохраняется
    public function test_excursion_upload_image(): void
    public function test_excursion_wysiwyg_image_upload(): void
    // POST /admin/upload/image с валидным файлом -> JSON { location: ... }
}
```

**Файл**: `tests/Feature/Public/ExcursionPageTest.php`

```php
class ExcursionPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_excursions_page_returns_200(): void
    public function test_excursion_show_returns_200(): void
    public function test_excursion_show_returns_404_for_unpublished(): void
    public function test_excursion_show_returns_404_for_nonexistent_slug(): void
}
```

---

## Фаза 4: Исторические статьи (rich-контент + импорт docx)

**Цель**: 4 статьи с WYSIWYG, self-referencing parent_id, импорт из Word.

---

### 4.1. Зависимость

```bash
composer require phpoffice/phpword
```

### 4.2. Миграция: `create_articles_table`

**Файл**: `database/migrations/2026_04_01_000004_create_articles_table.php`

```sql
CREATE TABLE articles (
    id                BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    slug              VARCHAR(191) NOT NULL UNIQUE,
    title             VARCHAR(255) NOT NULL,
    content           LONGTEXT NOT NULL,         -- WYSIWYG HTML
    image_path        VARCHAR(255) NULL,
    parent_id         BIGINT UNSIGNED NULL,
    is_published      BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order        INT UNSIGNED NOT NULL DEFAULT 0,
    meta_title        VARCHAR(255) NULL,
    meta_description  VARCHAR(500) NULL,
    created_at        TIMESTAMP NULL,
    updated_at        TIMESTAMP NULL,
    deleted_at        TIMESTAMP NULL,

    UNIQUE INDEX idx_articles_slug (slug),
    INDEX idx_articles_parent (parent_id),
    INDEX idx_articles_published (is_published, sort_order),

    CONSTRAINT fk_articles_parent FOREIGN KEY (parent_id)
        REFERENCES articles (id) ON DELETE SET NULL
);
```

Schema-код:
```php
Schema::create('articles', function (Blueprint $table) {
    $table->id();
    $table->string('slug', 191)->unique();
    $table->string('title', 255);
    $table->longText('content');
    $table->string('image_path', 255)->nullable();
    $table->unsignedBigInteger('parent_id')->nullable();
    $table->boolean('is_published')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->string('meta_title', 255)->nullable();
    $table->string('meta_description', 500)->nullable();
    $table->timestamps();
    $table->softDeletes();

    $table->index(['is_published', 'sort_order']);
    $table->foreign('parent_id')
        ->references('id')
        ->on('articles')
        ->onDelete('set null');
});
```

### 4.3. Модель: Article

**Файл**: `app/Models/Article.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Stevebauman\Purify\Facades\Purify;

class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'image_path',
        'parent_id',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
        'parent_id' => 'integer',
    ];

    // --- Relationships ---

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Article::class, 'parent_id')->orderBy('sort_order');
    }

    // --- Мутаторы ---

    public function setContentAttribute(string $value): void
    {
        $this->attributes['content'] = Purify::clean($value);
    }

    // --- Scopes ---

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // --- Accessors ---

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Массив breadcrumb-ов для вложенной статьи.
     *
     * @return array<array{title: string, url: string|null}>
     */
    public function getBreadcrumbsAttribute(): array
    {
        $crumbs = [['title' => 'Главная', 'url' => route('home')]];
        if ($this->parent) {
            $crumbs[] = [
                'title' => $this->parent->title,
                'url' => route('article.show', $this->parent),
            ];
        }
        $crumbs[] = ['title' => $this->title, 'url' => null];
        return $crumbs;
    }

    // --- Boot ---

    protected static function booted(): void
    {
        static::forceDeleting(function (Article $article) {
            if ($article->image_path) {
                app(\App\Services\ImageUploadService::class)->delete($article->image_path);
            }
        });
    }
}
```

### 4.4. Сервис: ArticleImportService

**Файл**: `app/Services/ArticleImportService.php`

```php
namespace App\Services;

use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\Writer\HTML as HtmlWriter;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use ZipArchive;

class ArticleImportService
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * Конвертировать .docx файл в HTML.
     *
     * @param UploadedFile $file Загруженный .docx
     * @return string HTML-контент для вставки в TinyMCE
     *
     * Логика:
     * 1. Валидация: только .docx, max 10MB
     * 2. PhpWord::load() -> HTMLWriter -> получить raw HTML
     * 3. Извлечь изображения из docx (ZipArchive -> word/media/)
     * 4. Сохранить изображения через ImageUploadService
     * 5. Заменить ссылки в HTML на реальные URL
     * 6. Очистить Word-мусор (mso-стили, пустые span, class="Mso*")
     * 7. Вернуть чистый HTML
     */
    public function import(UploadedFile $file): string
    {
        // 1. Загрузить docx во временный файл
        $tempPath = $file->getRealPath();

        // 2. PhpWord -> HTML
        $phpWord = IOFactory::load($tempPath, 'Word2007');
        $htmlWriter = IOFactory::createWriter($phpWord, 'HTML');

        ob_start();
        $htmlWriter->save('php://output');
        $rawHtml = ob_get_clean();

        // 3. Извлечь body из полного HTML
        $body = $this->extractBody($rawHtml);

        // 4. Извлечь и сохранить изображения
        $body = $this->processImages($file, $body);

        // 5. Очистить Word-мусор
        $body = $this->cleanWordHtml($body);

        return $body;
    }

    /**
     * Извлечь содержимое <body> из HTML.
     */
    private function extractBody(string $html): string
    {
        if (preg_match('/<body[^>]*>(.*)<\/body>/si', $html, $matches)) {
            return trim($matches[1]);
        }
        return $html;
    }

    /**
     * Извлечь изображения из docx через ZipArchive,
     * сохранить в storage, заменить ссылки в HTML.
     */
    private function processImages(UploadedFile $file, string $html): string
    {
        $zip = new ZipArchive();
        if ($zip->open($file->getRealPath()) !== true) {
            return $html;
        }

        // Найти все изображения в word/media/
        $imageMap = [];
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $name = $zip->getNameIndex($i);
            if (str_starts_with($name, 'word/media/')) {
                $imageData = $zip->getFromIndex($i);
                $ext = pathinfo($name, PATHINFO_EXTENSION);

                // Пропустить неподдерживаемые форматы
                if (!in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
                    continue;
                }

                // Сохранить во временный файл, затем через ImageUploadService
                $tempFile = tempnam(sys_get_temp_dir(), 'docx_img_');
                file_put_contents($tempFile, $imageData);

                $uploadedFile = new UploadedFile(
                    $tempFile,
                    Str::uuid() . '.' . $ext,
                    mime_content_type($tempFile),
                    null,
                    true // test mode
                );

                $path = $this->imageService->upload($uploadedFile, 'content');
                $imageMap[basename($name)] = asset('storage/' . $path);

                @unlink($tempFile);
            }
        }
        $zip->close();

        // Заменить ссылки на изображения в HTML
        foreach ($imageMap as $originalName => $newUrl) {
            $html = str_replace($originalName, $newUrl, $html);
        }

        return $html;
    }

    /**
     * Очистка Word-мусора из HTML.
     */
    private function cleanWordHtml(string $html): string
    {
        // Удалить mso-стили
        $html = preg_replace('/\s*mso-[^:]+:[^;"]+;?/i', '', $html);

        // Удалить class="Mso*"
        $html = preg_replace('/\s*class="Mso[^"]*"/i', '', $html);

        // Удалить пустые span
        $html = preg_replace('/<span[^>]*>\s*<\/span>/i', '', $html);

        // Удалить пустые параграфы (только &nbsp; или пробелы)
        $html = preg_replace('/<p[^>]*>(\s|&nbsp;)*<\/p>/i', '', $html);

        // Удалить style="" атрибуты
        $html = preg_replace('/\s*style="[^"]*"/i', '', $html);

        // Удалить комментарии
        $html = preg_replace('/<!--.*?-->/s', '', $html);

        // Нормализовать множественные пробелы/переносы
        $html = preg_replace('/\n{3,}/', "\n\n", $html);

        return trim($html);
    }
}
```

### 4.5. Контроллер: ArticleController (Admin)

**Файл**: `app/Http/Controllers/Admin/ArticleController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleImportService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ArticleController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
        private readonly ArticleImportService $importService,
    ) {}

    /**
     * GET /admin/articles
     */
    public function index(): View
    {
        $articles = Article::with('parent')
            ->orderBy('sort_order')
            ->paginate(20);

        return view('admin.articles.index', compact('articles'));
    }

    /**
     * GET /admin/articles/create
     */
    public function create(): View
    {
        // Список возможных родителей (для select)
        $parents = Article::roots()->published()->ordered()->get(['id', 'title']);
        return view('admin.articles.create', compact('parents'));
    }

    /**
     * POST /admin/articles
     *
     * Валидация:
     *   'slug'             => ['required', 'string', 'max:191', 'alpha_dash', 'unique:articles,slug']
     *   'title'            => ['required', 'string', 'max:255']
     *   'content'          => ['required', 'string', 'max:500000']
     *   'image'            => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
     *   'parent_id'        => ['nullable', 'integer', 'exists:articles,id']
     *   'is_published'     => ['boolean']
     *   'sort_order'       => ['integer', 'min:0']
     *   'meta_title'       => ['nullable', 'string', 'max:255']
     *   'meta_description' => ['nullable', 'string', 'max:500']
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', 'unique:articles,slug'],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'parent_id' => ['nullable', 'integer', 'exists:articles,id'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageService->upload($request->file('image'), 'articles');
        }

        Article::create(array_merge(
            collect($validated)->except('image')->toArray(),
            ['image_path' => $imagePath, 'is_published' => $validated['is_published'] ?? false]
        ));

        return redirect()->route('admin.articles.index')->with('success', 'Статья создана');
    }

    /**
     * GET /admin/articles/{article}/edit
     */
    public function edit(Article $article): View
    {
        $parents = Article::roots()
            ->where('id', '!=', $article->id)
            ->published()
            ->ordered()
            ->get(['id', 'title']);

        return view('admin.articles.edit', compact('article', 'parents'));
    }

    /**
     * PUT /admin/articles/{article}
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate([
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', Rule::unique('articles', 'slug')->ignore($article->id)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string', 'max:500000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'parent_id' => ['nullable', 'integer', 'exists:articles,id'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ]);

        // Защита от self-reference
        if (($validated['parent_id'] ?? null) == $article->id) {
            $validated['parent_id'] = null;
        }

        $data = collect($validated)->except('image')->toArray();
        $data['is_published'] = $validated['is_published'] ?? false;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace(
                $article->image_path, $request->file('image'), 'articles'
            );
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('success', 'Статья обновлена');
    }

    /**
     * DELETE /admin/articles/{article}
     */
    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();
        return redirect()->route('admin.articles.index')->with('success', 'Статья удалена');
    }

    /**
     * POST /admin/articles/import
     * AJAX: конвертация .docx в HTML для вставки в TinyMCE.
     *
     * Валидация:
     *   'file' => ['required', 'file', 'mimes:docx', 'max:10240']
     *
     * @return JsonResponse { html: "..." } | { error: "..." }
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
        ]);

        try {
            $html = $this->importService->import($request->file('file'));
            return response()->json(['html' => $html]);
        } catch (\Throwable $e) {
            return response()->json(['error' => 'Ошибка импорта: ' . $e->getMessage()], 422);
        }
    }
}
```

### 4.6. PageController (дополнение)

```php
/**
 * GET /article/{article:slug}
 */
public function articleShow(Article $article): View
{
    abort_unless($article->is_published, 404);
    $article->load('parent');

    return view('pages.article-show', compact('article'));
}
```

### 4.7. Роуты

#### admin.php

```php
Route::resource('articles', ArticleController::class)->except(['show']);
Route::post('/articles/import', [ArticleController::class, 'import'])->name('articles.import');
```

#### web.php

```php
Route::get('/article/{article:slug}', [PageController::class, 'articleShow'])->name('article.show');

// 301 редиректы со старых URL
Route::redirect('/military-town', '/article/military-town', 301);
Route::redirect('/junker-school', '/article/junker-school', 301);
Route::redirect('/infantry-courses', '/article/infantry-courses', 301);
Route::redirect('/topographic-unit', '/article/topographic-unit', 301);
```

Удалить старые роуты `/military-town`, `/junker-school`, `/infantry-courses`, `/topographic-unit`.

### 4.8. Views

#### pages/article-show.blade.php (новый)

**Файл**: `resources/views/pages/article-show.blade.php`

Extends: `layouts.app`.
Переменные: `$article` (Article с eager-loaded parent).

```blade
@extends('layouts.app')
@section('title', ($article->meta_title ?: $article->title) . ' -- Музей "Иркутское юнкерское училище"')

@section('content')
<div class="page">
    <x-breadcrumbs :items="$article->breadcrumbs" />
    <h2 class="page__title">{{ $article->title }}</h2>
    <div class="article">
        {!! $article->content !!}
    </div>
</div>
@endsection
```

#### admin/articles/_form.blade.php

Поля: slug, title, parent_id (select из $parents), content (textarea.wysiwyg), image (file), is_published (checkbox), sort_order, meta_title, meta_description.

Кнопка "Импорт из Word":
```blade
<div class="admin-form__group">
    <label class="admin-form__label">Импорт из Word (.docx)</label>
    <input type="file" id="docx-import" accept=".docx" class="admin-form__input">
    <small class="admin-form__hint">Макс. 10 МБ. Проверьте результат импорта -- возможна потеря форматирования.</small>
</div>
```

JS для импорта (в @push('scripts')):
```javascript
document.getElementById('docx-import')?.addEventListener('change', function () {
    var file = this.files[0];
    if (!file) return;

    var formData = new FormData();
    formData.append('file', file);

    var xhr = new XMLHttpRequest();
    xhr.open('POST', '{{ route("admin.articles.import") }}');
    xhr.setRequestHeader('X-CSRF-TOKEN', document.querySelector('meta[name="csrf-token"]').content);

    xhr.onload = function () {
        if (xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.html) {
                // Вставить HTML в TinyMCE
                tinymce.activeEditor.setContent(response.html);
                alert('Импорт завершён. Проверьте результат и скорректируйте при необходимости.');
            }
        } else {
            var error = JSON.parse(xhr.responseText);
            alert(error.error || 'Ошибка импорта');
        }
    };

    xhr.onerror = function () {
        alert('Ошибка сети');
    };

    xhr.send(formData);
    this.value = ''; // Сбросить input
});
```

#### components/header.blade.php (обновить dropdown)

Dropdown "Военный городок" -- из БД:

```blade
{{-- Заменить хардкод на динамический вывод --}}
<ul class="nav__dropdown">
    @foreach ($navArticles as $article)
    <li>
        <a href="{{ route('article.show', $article) }}" class="nav__dropdown-link">
            {{ $article->title }}
        </a>
    </li>
    @endforeach
</ul>
```

Для передачи `$navArticles` -- дополнительный View Composer:

```php
// AppServiceProvider::boot()
View::composer('components.header', function ($view) {
    // Статьи-дети military-town (parent_id = military-town article)
    $militaryTown = \App\Models\Article::where('slug', 'military-town')->first();
    $navArticles = $militaryTown
        ? \App\Models\Article::where('parent_id', $militaryTown->id)
            ->published()
            ->ordered()
            ->get(['id', 'slug', 'title'])
        : collect();
    $view->with('navArticles', $navArticles);
});
```

#### pages/home.blade.php (обновить дерево формирований)

Передать `$formationsTree` из PageController::home():

```blade
<div class="formations__tree" id="formationsTree">
    @foreach ($formationsTree as $article)
        @if ($loop->first)
            <a href="{{ route('article.show', $article) }}" class="formations__item formations__item--main">
                <span>{{ $article->title }}</span>
                <span class="formations__triangle">&#9658;</span>
            </a>
        @else
            <div class="formations__arrow">
                <svg width="2" height="36" viewBox="0 0 2 36">
                    <line x1="1" y1="0" x2="1" y2="30" stroke="#8B1A1A" stroke-width="2"/>
                    <polygon points="0,30 2,30 1,36" fill="#8B1A1A"/>
                </svg>
            </div>
            <a href="{{ route('article.show', $article) }}" class="formations__item">
                {{ $article->title }}
            </a>
        @endif
    @endforeach
</div>
```

### 4.9. main.js (обновить)

Заменить хардкод route names на data-атрибуты:

```javascript
// Было:
const militaryPages = ['junker-school', 'infantry-courses', 'topographic-unit', 'military-town'];

// Стало: определять принадлежность через data-nav-group на body
// В layouts.app добавить: data-nav-group="{{ $navGroup ?? '' }}"
// PageController::articleShow() передаёт 'navGroup' => 'military-town' для вложенных статей

const navGroup = document.body.getAttribute('data-nav-group') || '';
if (navGroup === 'military-town' && link.classList.contains('nav__link--has-dropdown')) {
    link.classList.add('nav__link--active');
}
if (navGroup === 'excursions' && routeName === 'excursions') {
    link.classList.add('nav__link--active');
}
```

### 4.10. Seeder: ArticlesSeeder

**Файл**: `database/seeders/ArticlesSeeder.php`

4 статьи. Содержимое берётся из `<div class="article">...</div>` текущих шаблонов (без Blade-директив):

```php
$militaryTown = Article::updateOrCreate(
    ['slug' => 'military-town'],
    [
        'title' => 'Воинские формирования, занимавшие здания военного городка',
        'content' => '...HTML из military-town.blade.php...',
        'parent_id' => null,
        'sort_order' => 1,
    ]
);

Article::updateOrCreate(
    ['slug' => 'junker-school'],
    [
        'title' => 'Иркутское юнкерское (военное) училище (1874-1918 гг.)',
        'content' => '...HTML из junker-school.blade.php...',
        'parent_id' => $militaryTown->id,
        'sort_order' => 1,
    ]
);

Article::updateOrCreate(
    ['slug' => 'infantry-courses'],
    [
        'title' => 'Пехотные курсы командиров РККА (1920-1933 гг.)',
        'content' => '...HTML из infantry-courses.blade.php...',
        'parent_id' => $militaryTown->id,
        'sort_order' => 2,
    ]
);

Article::updateOrCreate(
    ['slug' => 'topographic-unit'],
    [
        'title' => 'Топографический отряд (1934 г. -- н. в.)',
        'content' => '...HTML из topographic-unit.blade.php...',
        'parent_id' => $militaryTown->id,
        'sort_order' => 3,
    ]
);
```

### 4.11. Удаление старых файлов

После верификации:
- `pages/military-town.blade.php`
- `pages/junker-school.blade.php`
- `pages/infantry-courses.blade.php`
- `pages/topographic-unit.blade.php`

### 4.12. Тесты: Фаза 4

**Файл**: `tests/Feature/Admin/ArticleTest.php`

```php
class ArticleTest extends TestCase
{
    use RefreshDatabase;

    public function test_articles_index_redirects_guests(): void
    public function test_authenticated_user_can_create_article(): void
    public function test_authenticated_user_can_update_article(): void
    public function test_authenticated_user_can_delete_article(): void
    public function test_article_store_requires_unique_slug(): void
    public function test_article_content_sanitized(): void
    public function test_article_self_reference_prevented(): void
    // parent_id == article.id -> parent_id = null
    public function test_article_parent_id_fk_exists(): void
    // parent_id = несуществующий id -> validation error
    public function test_article_import_docx(): void
    // POST /admin/articles/import с .docx файлом -> JSON { html: ... }
    public function test_article_import_rejects_non_docx(): void
    // POST /admin/articles/import с .pdf -> validation error
    public function test_article_import_rejects_oversized(): void
    // POST с файлом > 10MB -> validation error
}
```

**Файл**: `tests/Feature/Public/ArticlePageTest.php`

```php
class ArticlePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_article_show_returns_200(): void
    public function test_article_show_returns_404_for_unpublished(): void
    public function test_old_urls_redirect_301(): void
    // GET /military-town -> 301 -> /article/military-town
    public function test_article_breadcrumbs_include_parent(): void
}
```

---

## Фаза 5: Экспозиция + Архив (карточки)

**Цель**: единая таблица catalog_items с type для exposition/archive.

---

### 5.1. Миграция: `create_catalog_items_table`

**Файл**: `database/migrations/2026_04_01_000005_create_catalog_items_table.php`

```sql
CREATE TABLE catalog_items (
    id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    type          VARCHAR(20) NOT NULL,            -- 'exposition' | 'archive'
    title         VARCHAR(255) NOT NULL,
    description   TEXT NOT NULL,
    image_path    VARCHAR(255) NULL,
    link_url      VARCHAR(255) NULL,
    is_published  BOOLEAN NOT NULL DEFAULT TRUE,
    sort_order    INT UNSIGNED NOT NULL DEFAULT 0,
    created_at    TIMESTAMP NULL,
    updated_at    TIMESTAMP NULL,

    INDEX idx_catalog_type_published (type, is_published, sort_order)
);
```

Schema-код:
```php
Schema::create('catalog_items', function (Blueprint $table) {
    $table->id();
    $table->string('type', 20);
    $table->string('title', 255);
    $table->text('description');
    $table->string('image_path', 255)->nullable();
    $table->string('link_url', 255)->nullable();
    $table->boolean('is_published')->default(true);
    $table->unsignedInteger('sort_order')->default(0);
    $table->timestamps();

    $table->index(['type', 'is_published', 'sort_order']);
});
```

Без SoftDeletes -- простые карточки, удаление окончательное.

### 5.2. Модель: CatalogItem

**Файл**: `app/Models/CatalogItem.php`

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class CatalogItem extends Model
{
    public const TYPE_EXPOSITION = 'exposition';
    public const TYPE_ARCHIVE = 'archive';

    public const VALID_TYPES = [self::TYPE_EXPOSITION, self::TYPE_ARCHIVE];

    protected $fillable = [
        'type',
        'title',
        'description',
        'image_path',
        'link_url',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'sort_order' => 'integer',
    ];

    // --- Scopes ---

    public function scopeExposition(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_EXPOSITION);
    }

    public function scopeArchive(Builder $query): Builder
    {
        return $query->where('type', self::TYPE_ARCHIVE);
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    // --- Accessors ---

    public function getImageUrlAttribute(): ?string
    {
        return $this->image_path ? asset('storage/' . $this->image_path) : null;
    }

    /**
     * Человекочитаемое название типа.
     */
    public function getTypeNameAttribute(): string
    {
        return match ($this->type) {
            self::TYPE_EXPOSITION => 'Экспозиция',
            self::TYPE_ARCHIVE => 'Архив',
            default => $this->type,
        };
    }

    // --- Boot ---

    protected static function booted(): void
    {
        // Без SoftDeletes -- удаляем файл сразу
        static::deleting(function (CatalogItem $item) {
            if ($item->image_path) {
                app(\App\Services\ImageUploadService::class)->delete($item->image_path);
            }
        });
    }
}
```

### 5.3. Контроллер: CatalogController (Admin)

**Файл**: `app/Http/Controllers/Admin/CatalogController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CatalogController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * GET /admin/catalog/{type}
     * type: 'exposition' | 'archive'
     */
    public function index(string $type): View
    {
        abort_unless(in_array($type, CatalogItem::VALID_TYPES, true), 404);

        $items = CatalogItem::where('type', $type)
            ->ordered()
            ->paginate(20);

        $typeName = $type === 'exposition' ? 'Экспозиция' : 'Архив';

        return view('admin.catalog.index', compact('items', 'type', 'typeName'));
    }

    /**
     * GET /admin/catalog/{type}/create
     */
    public function create(string $type): View
    {
        abort_unless(in_array($type, CatalogItem::VALID_TYPES, true), 404);
        $typeName = $type === 'exposition' ? 'Экспозиция' : 'Архив';
        return view('admin.catalog.create', compact('type', 'typeName'));
    }

    /**
     * POST /admin/catalog/{type}
     *
     * Валидация:
     *   'title'       => ['required', 'string', 'max:255']
     *   'description' => ['required', 'string', 'max:2000']
     *   'image'       => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096']
     *   'link_url'    => ['nullable', 'url', 'max:255']
     *   'is_published'=> ['boolean']
     *   'sort_order'  => ['integer', 'min:0']
     */
    public function store(Request $request, string $type): RedirectResponse
    {
        abort_unless(in_array($type, CatalogItem::VALID_TYPES, true), 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $this->imageService->upload($request->file('image'), 'catalog');
        }

        CatalogItem::create([
            'type' => $type,
            'title' => $validated['title'],
            'description' => $validated['description'],
            'image_path' => $imagePath,
            'link_url' => $validated['link_url'] ?? null,
            'is_published' => $validated['is_published'] ?? false,
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        return redirect()->route('admin.catalog.index', $type)->with('success', 'Элемент создан');
    }

    /**
     * GET /admin/catalog/{type}/{catalogItem}/edit
     */
    public function edit(string $type, CatalogItem $catalogItem): View
    {
        abort_unless($catalogItem->type === $type, 404);
        $typeName = $type === 'exposition' ? 'Экспозиция' : 'Архив';
        return view('admin.catalog.edit', compact('catalogItem', 'type', 'typeName'));
    }

    /**
     * PUT /admin/catalog/{type}/{catalogItem}
     */
    public function update(Request $request, string $type, CatalogItem $catalogItem): RedirectResponse
    {
        abort_unless($catalogItem->type === $type, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string', 'max:2000'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'link_url' => ['nullable', 'url', 'max:255'],
            'is_published' => ['boolean'],
            'sort_order' => ['integer', 'min:0'],
        ]);

        $data = collect($validated)->except('image')->toArray();
        $data['is_published'] = $validated['is_published'] ?? false;

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace(
                $catalogItem->image_path, $request->file('image'), 'catalog'
            );
        }

        $catalogItem->update($data);

        return redirect()->route('admin.catalog.index', $type)->with('success', 'Элемент обновлен');
    }

    /**
     * DELETE /admin/catalog/{type}/{catalogItem}
     */
    public function destroy(string $type, CatalogItem $catalogItem): RedirectResponse
    {
        abort_unless($catalogItem->type === $type, 404);
        $catalogItem->delete();
        return redirect()->route('admin.catalog.index', $type)->with('success', 'Элемент удалён');
    }
}
```

### 5.4. Роуты

#### admin.php

```php
Route::prefix('catalog/{type}')->name('catalog.')->where(['type' => 'exposition|archive'])->group(function () {
    Route::get('/', [CatalogController::class, 'index'])->name('index');
    Route::get('/create', [CatalogController::class, 'create'])->name('create');
    Route::post('/', [CatalogController::class, 'store'])->name('store');
    Route::get('/{catalogItem}/edit', [CatalogController::class, 'edit'])->name('edit');
    Route::put('/{catalogItem}', [CatalogController::class, 'update'])->name('update');
    Route::delete('/{catalogItem}', [CatalogController::class, 'destroy'])->name('destroy');
});
```

#### web.php

Заменить:
```php
Route::get('/exposition', fn () => view('pages.exposition'))->name('exposition');
Route::get('/archive', fn () => view('pages.archive'))->name('archive');
```
На:
```php
Route::get('/exposition', [PageController::class, 'exposition'])->name('exposition');
Route::get('/archive', [PageController::class, 'archive'])->name('archive');
```

### 5.5. PageController (дополнение)

```php
public function exposition(): View
{
    $items = CatalogItem::exposition()->published()->ordered()->get();
    return view('pages.exposition', compact('items'));
}

public function archive(): View
{
    $items = CatalogItem::archive()->published()->ordered()->get();
    return view('pages.archive', compact('items'));
}
```

### 5.6. Views

#### pages/exposition.blade.php (обновить)

```blade
@forelse ($items as $item)
<div class="card">
    <div class="card__image">
        @if ($item->image_url)
            <img src="{{ $item->image_url }}" alt="{{ $item->title }}" loading="lazy">
        @else
            Фото экспозиции
        @endif
    </div>
    <div class="card__body">
        <h3 class="card__title">{{ $item->title }}</h3>
        <p class="card__text">{{ $item->description }}</p>
        @if ($item->link_url)
            <a href="{{ $item->link_url }}" class="card__link">Подробнее &rarr;</a>
        @endif
    </div>
</div>
@empty
<p>Экспозиция пока не заполнена.</p>
@endforelse
```

pages/archive.blade.php -- аналогичная структура.

### 5.7. Seeder: CatalogSeeder

**Файл**: `database/seeders/CatalogSeeder.php`

6 exposition + 6 archive карточек из текущих шаблонов.

### 5.8. Тесты: Фаза 5

**Файл**: `tests/Feature/Admin/CatalogTest.php`

```php
class CatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_index_redirects_guests(): void
    public function test_catalog_index_returns_200_for_exposition(): void
    public function test_catalog_index_returns_200_for_archive(): void
    public function test_catalog_index_returns_404_for_invalid_type(): void
    public function test_authenticated_user_can_create_catalog_item(): void
    public function test_authenticated_user_can_update_catalog_item(): void
    public function test_authenticated_user_can_delete_catalog_item(): void
    public function test_catalog_store_validates_title(): void
    public function test_catalog_edit_rejects_wrong_type(): void
    // GET /admin/catalog/archive/{exposition_item}/edit -> 404
}
```

**Файл**: `tests/Feature/Public/CatalogPageTest.php`

```php
class CatalogPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_exposition_page_returns_200(): void
    public function test_archive_page_returns_200(): void
    public function test_exposition_shows_published_items(): void
    public function test_exposition_hides_unpublished_items(): void
}
```

---

## Фаза 6: О музее + Главная

**Цель**: оставшиеся страницы через settings.

---

### 6.1. SettingsController (обновить)

Добавить таб "О музее" в форму settings с WYSIWYG-полями:

Новые правила валидации:
```php
'settings.about.history' => ['nullable', 'string', 'max:50000'],
'settings.about.mission' => ['nullable', 'string', 'max:50000'],
```

Санитизация HTML при сохранении about.history и about.mission:
```php
// В методе update(), после flattenArray:
foreach ($flat as $key => $value) {
    if (in_array($key, Setting::ALLOWED_KEYS, true)) {
        // HTML-санитизация для ключей с HTML-контентом
        if (in_array($key, ['modals.about', 'about.history', 'about.mission']) && $value) {
            $value = Purify::clean($value);
        }
        Setting::set($key, $value);
    }
}
```

Добавить таб "Изображения" для загрузки home.building_image:
```php
'building_image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
```

### 6.2. pages/about.blade.php (обновить)

```blade
<div class="about-section">
    <h3>История музея</h3>
    @if ($settings['about.history'] ?? null)
        {!! $settings['about.history'] !!}
    @else
        {{-- Fallback на текущий хардкод --}}
        <p>Музей "Иркутское юнкерское училище" ведёт свою историю...</p>
    @endif
</div>

<div class="about-section">
    <h3>Миссия и деятельность</h3>
    @if ($settings['about.mission'] ?? null)
        {!! $settings['about.mission'] !!}
    @else
        <p>Миссия музея -- сохранение и популяризация...</p>
    @endif
</div>
```

### 6.3. PageController::home()

```php
public function home(): View
{
    $excursions = Excursion::published()->ordered()->get();
    $formationsTree = Article::where('slug', 'military-town')
        ->orWhere(function ($q) {
            $militaryTown = Article::where('slug', 'military-town')->first();
            if ($militaryTown) {
                $q->where('parent_id', $militaryTown->id);
            }
        })
        ->published()
        ->ordered()
        ->get();

    return view('pages.home', compact('excursions', 'formationsTree'));
}
```

web.php:
```php
Route::get('/', [PageController::class, 'home'])->name('home');
```

### 6.4. Тесты: Фаза 6

**Файл**: `tests/Feature/Public/HomePageTest.php`

```php
class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_page_returns_200(): void
    public function test_home_page_shows_excursions(): void
    public function test_home_page_shows_formations_tree(): void
}
```

---

## Фаза 7: Полировка

### 7.1. Drag & drop сортировка

**Зависимость**: SortableJS через CDN (`<script src="https://cdn.jsdelivr.net/npm/sortablejs@1/Sortable.min.js"></script>`).

**Контроллер**: `app/Http/Controllers/Admin/ReorderController.php`

```php
namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReorderController extends Controller
{
    /**
     * POST /admin/{entity}/reorder
     * entity: excursions | catalog-exposition | catalog-archive
     *
     * Валидация:
     *   'ids'   => ['required', 'array']
     *   'ids.*' => ['integer']
     *
     * Логика: обновить sort_order для каждого id по индексу в массиве.
     */
    public function reorder(Request $request, string $entity): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer'],
        ]);

        $modelClass = match ($entity) {
            'excursions' => \App\Models\Excursion::class,
            'catalog-exposition', 'catalog-archive' => \App\Models\CatalogItem::class,
            default => abort(404),
        };

        foreach ($validated['ids'] as $index => $id) {
            $modelClass::where('id', $id)->update(['sort_order' => $index]);
        }

        return response()->json(['success' => true]);
    }
}
```

Роут (admin.php):
```php
Route::post('/{entity}/reorder', [ReorderController::class, 'reorder'])
    ->name('reorder')
    ->where('entity', 'excursions|catalog-exposition|catalog-archive');
```

JS для таблиц с sortable:
```javascript
// Инициализация SortableJS на tbody таблиц с data-sortable
document.querySelectorAll('[data-sortable]').forEach(function (tbody) {
    new Sortable(tbody, {
        animation: 150,
        handle: '.admin-table__drag-handle',
        onEnd: function () {
            var ids = Array.from(tbody.querySelectorAll('tr[data-id]')).map(function (row) {
                return parseInt(row.dataset.id, 10);
            });
            var entity = tbody.dataset.sortable;

            fetch('/admin/' + entity + '/reorder', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ ids: ids }),
            });
        },
    });
});
```

### 7.2. Dashboard (обновить)

```php
// DashboardController::index()
public function index(): View
{
    $counts = [
        'news' => News::count(),
        'excursions' => Excursion::count(),
        'articles' => Article::count(),
        'exposition' => CatalogItem::exposition()->count(),
        'archive' => CatalogItem::archive()->count(),
    ];

    $recentNews = News::orderByDesc('created_at')->take(5)->get();

    return view('admin.dashboard', compact('counts', 'recentNews'));
}
```

### 7.3. Все routes через PageController

Заменить оставшиеся замыкания в web.php:

```php
Route::get('/', [PageController::class, 'home'])->name('home');
Route::get('/news', [PageController::class, 'news'])->name('news');
Route::get('/exposition', [PageController::class, 'exposition'])->name('exposition');
Route::get('/archive', [PageController::class, 'archive'])->name('archive');
Route::get('/about', [PageController::class, 'about'])->name('about');
Route::get('/contacts', [PageController::class, 'contacts'])->name('contacts');
Route::get('/excursions', [PageController::class, 'excursions'])->name('excursions');
Route::get('/excursion/{excursion:slug}', [PageController::class, 'excursionShow'])->name('excursion.show');
Route::get('/article/{article:slug}', [PageController::class, 'articleShow'])->name('article.show');

// 301 редиректы
Route::redirect('/military-town', '/article/military-town', 301);
Route::redirect('/junker-school', '/article/junker-school', 301);
Route::redirect('/infantry-courses', '/article/infantry-courses', 301);
Route::redirect('/topographic-unit', '/article/topographic-unit', 301);
```

### 7.4. Кеширование

```php
// AppServiceProvider::boot()
View::composer('layouts.app', function ($view) {
    $view->with('settings', Setting::cached());
});
```

Кеш Settings уже работает (Cache::rememberForever). Дополнительно: кеширование публичных страниц через HTTP Cache headers (Cache-Control: public, max-age=300) в middleware.

**Файл**: `app/Http/Middleware/CachePublicPages.php`

```php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublicPages
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && !$request->is('admin/*')) {
            $response->headers->set('Cache-Control', 'public, max-age=300');
        }

        return $response;
    }
}
```

### 7.5. Orphaned WYSIWYG images

**Файл**: `app/Console/Commands/CleanOrphanedImages.php`

```php
namespace App\Console\Commands;

use App\Models\Article;
use App\Models\Excursion;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanOrphanedImages extends Command
{
    protected $signature = 'media:clean-orphaned {--dry-run : Показать файлы без удаления}';
    protected $description = 'Удалить файлы из uploads/content/, не упоминаемые в контенте';

    public function handle(): int
    {
        // 1. Собрать все URL файлов из WYSIWYG-контента
        $usedUrls = collect();

        Article::all()->each(function ($article) use (&$usedUrls) {
            preg_match_all('/uploads\/content\/[^\s"\']+/', $article->content, $matches);
            $usedUrls = $usedUrls->merge($matches[0]);
        });

        Excursion::all()->each(function ($excursion) use (&$usedUrls) {
            foreach (['description', 'what_you_see', 'interesting_facts'] as $field) {
                if ($excursion->$field) {
                    preg_match_all('/uploads\/content\/[^\s"\']+/', $excursion->$field, $matches);
                    $usedUrls = $usedUrls->merge($matches[0]);
                }
            }
        });

        $usedUrls = $usedUrls->unique()->values();

        // 2. Получить все файлы в uploads/content/
        $allFiles = Storage::disk('public')->files('uploads/content');

        // 3. Найти orphaned
        $orphaned = collect($allFiles)->filter(function ($file) use ($usedUrls) {
            return !$usedUrls->contains($file);
        });

        if ($orphaned->isEmpty()) {
            $this->info('Осиротевших файлов не найдено.');
            return 0;
        }

        $this->info("Найдено осиротевших файлов: {$orphaned->count()}");

        if ($this->option('dry-run')) {
            $orphaned->each(fn ($f) => $this->line("  $f"));
            return 0;
        }

        $orphaned->each(function ($file) {
            Storage::disk('public')->delete($file);
            $this->line("  Удалён: $file");
        });

        $this->info('Готово.');
        return 0;
    }
}
```

### 7.6. Тесты: Фаза 7

**Файл**: `tests/Feature/Admin/ReorderTest.php`

```php
class ReorderTest extends TestCase
{
    use RefreshDatabase;

    public function test_reorder_redirects_guests(): void
    public function test_reorder_excursions(): void
    // POST с ids=[3,1,2] -> sort_order обновляется
    public function test_reorder_rejects_invalid_entity(): void
}
```

---

## Фаза SEO

**Цель**: полное SEO на всех страницах.

---

### SEO.1. Blade-компонент: seo-meta

**Файл**: `resources/views/components/seo-meta.blade.php`

```blade
@props([
    'title' => null,
    'description' => null,
    'ogImage' => null,
    'type' => 'website',
    'url' => null,
    'schema' => null,
])

@php
    $siteTitle = 'Музей "Иркутское юнкерское училище"';
    $fullTitle = $title ? $title . ' -- ' . $siteTitle : $siteTitle;
    $metaDescription = $description ?: 'Музей военной истории в Иркутске. Экспозиция юнкерского училища, пехотных курсов РККА и топографической службы.';
    $ogImageUrl = $ogImage ?: asset('images/ivu.jpg');
    $canonicalUrl = $url ?: request()->url();
@endphp

<title>{{ $fullTitle }}</title>
<meta name="description" content="{{ Str::limit(strip_tags($metaDescription), 160) }}">

{{-- Open Graph --}}
<meta property="og:title" content="{{ $fullTitle }}">
<meta property="og:description" content="{{ Str::limit(strip_tags($metaDescription), 200) }}">
<meta property="og:type" content="{{ $type }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:image" content="{{ $ogImageUrl }}">
<meta property="og:locale" content="ru_RU">
<meta property="og:site_name" content="{{ $siteTitle }}">

{{-- Canonical --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Schema.org JSON-LD --}}
@if ($schema)
<script type="application/ld+json">
{!! json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) !!}
</script>
@endif
```

### SEO.2. Обновить layouts/app.blade.php

```blade
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    {{-- SEO meta-теги --}}
    @hasSection('seo')
        @yield('seo')
    @else
        <x-seo-meta />
    @endif

    <link rel="stylesheet" href="{{ asset('css/style.css') }}">

    {{-- Analytics --}}
    @if ($settings['seo.analytics_yandex'] ?? null)
    <!-- Яндекс.Метрика -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
        for(var j=0;j<document.scripts.length;j++){if(document.scripts[j].src===r)return;}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        ym({{ $settings['seo.analytics_yandex'] }}, "init", {
            clickmap:true, trackLinks:true, accurateTrackBounce:true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/{{ $settings['seo.analytics_yandex'] }}" style="position:absolute;left:-9999px" alt=""></div></noscript>
    @endif

    @if ($settings['seo.analytics_google'] ?? null)
    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id={{ $settings['seo.analytics_google'] }}"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '{{ $settings['seo.analytics_google'] }}');
    </script>
    @endif
</head>
```

### SEO.3. SEO на каждой сущности

#### Главная

```blade
@section('seo')
<x-seo-meta
    :schema="[
        '@context' => 'https://schema.org',
        '@type' => 'Museum',
        'name' => 'Музей \"Иркутское юнкерское училище\"',
        'description' => 'Музей военной истории в Иркутске',
        'address' => [
            '@type' => 'PostalAddress',
            'streetAddress' => $settings['contacts.address'] ?? '',
            'addressLocality' => 'Иркутск',
            'addressCountry' => 'RU',
        ],
        'telephone' => $settings['contacts.phone'] ?? '',
        'email' => $settings['contacts.email'] ?? '',
        'url' => route('home'),
    ]"
/>
@endsection
```

#### Экскурсия

```blade
@section('seo')
<x-seo-meta
    :title="$excursion->meta_title ?: $excursion->title"
    :description="$excursion->meta_description ?: $excursion->short_description"
    :ogImage="$excursion->image_url"
    type="article"
    :schema="[
        '@context' => 'https://schema.org',
        '@type' => 'Event',
        'name' => $excursion->title,
        'description' => $excursion->short_description,
        'duration' => 'PT' . $excursion->duration_minutes . 'M',
        'location' => [
            '@type' => 'Museum',
            'name' => 'Музей \"Иркутское юнкерское училище\"',
        ],
        'url' => route('excursion.show', $excursion),
    ]"
/>
@endsection
```

#### Статья

```blade
@section('seo')
<x-seo-meta
    :title="$article->meta_title ?: $article->title"
    :description="$article->meta_description ?: Str::limit(strip_tags($article->content), 160)"
    :ogImage="$article->image_url"
    type="article"
    :schema="[
        '@context' => 'https://schema.org',
        '@type' => 'Article',
        'headline' => $article->title,
        'url' => route('article.show', $article),
        'dateModified' => $article->updated_at->toIso8601String(),
        'publisher' => [
            '@type' => 'Organization',
            'name' => 'Музей \"Иркутское юнкерское училище\"',
        ],
    ]"
/>
@endsection
```

#### Новости

```blade
@section('seo')
<x-seo-meta
    title="Новости"
    description="Новости музея \"Иркутское юнкерское училище\" -- выставки, лекции, экскурсии и события."
/>
@endsection
```

### SEO.4. sitemap.xml (динамический)

**Файл**: `app/Http/Controllers/SitemapController.php`

```php
namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Excursion;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    /**
     * GET /sitemap.xml
     */
    public function index(): Response
    {
        $urls = collect();

        // Статические страницы
        $static = ['home', 'news', 'exposition', 'archive', 'about', 'contacts', 'excursions'];
        foreach ($static as $name) {
            $urls->push([
                'loc' => route($name),
                'changefreq' => $name === 'news' ? 'weekly' : 'monthly',
                'priority' => $name === 'home' ? '1.0' : '0.8',
            ]);
        }

        // Экскурсии
        Excursion::published()->ordered()->each(function ($excursion) use (&$urls) {
            $urls->push([
                'loc' => route('excursion.show', $excursion),
                'lastmod' => $excursion->updated_at->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]);
        });

        // Статьи
        Article::published()->ordered()->each(function ($article) use (&$urls) {
            $urls->push([
                'loc' => route('article.show', $article),
                'lastmod' => $article->updated_at->toW3cString(),
                'changefreq' => 'monthly',
                'priority' => '0.7',
            ]);
        });

        $xml = view('sitemap', compact('urls'))->render();

        return response($xml, 200)
            ->header('Content-Type', 'application/xml');
    }
}
```

**Файл**: `resources/views/sitemap.blade.php`

```blade
<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
@foreach ($urls as $url)
    <url>
        <loc>{{ $url['loc'] }}</loc>
        @if (isset($url['lastmod']))
        <lastmod>{{ $url['lastmod'] }}</lastmod>
        @endif
        <changefreq>{{ $url['changefreq'] }}</changefreq>
        <priority>{{ $url['priority'] }}</priority>
    </url>
@endforeach
</urlset>
```

Роут (web.php):
```php
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
```

### SEO.5. robots.txt

**Файл**: `app/Http/Controllers/RobotsController.php`

```php
namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Response;

class RobotsController extends Controller
{
    /**
     * GET /robots.txt
     */
    public function index(): Response
    {
        $custom = Setting::get('seo.robots_txt');

        $content = $custom ?: implode("\n", [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin/',
            'Disallow: /storage/',
            '',
            'Sitemap: ' . route('sitemap'),
        ]);

        return response($content, 200)
            ->header('Content-Type', 'text/plain');
    }
}
```

Роут (web.php):
```php
Route::get('/robots.txt', [RobotsController::class, 'index']);
```

Удалить статический `public/robots.txt` (если есть).

### SEO.6. Настройки аналитики в админке

В SettingsController -- добавить таб "SEO":

Валидация:
```php
'settings.seo.analytics_yandex' => ['nullable', 'string', 'max:20', 'regex:/^\d+$/'],
'settings.seo.analytics_google' => ['nullable', 'string', 'max:30', 'regex:/^(G|UA)-[A-Z0-9-]+$/i'],
'settings.seo.robots_txt' => ['nullable', 'string', 'max:2000'],
```

UI: три поля -- ID Яндекс.Метрики, ID Google Analytics, содержимое robots.txt (textarea).

### SEO.7. Тесты: Фаза SEO

**Файл**: `tests/Feature/SeoTest.php`

```php
class SeoTest extends TestCase
{
    use RefreshDatabase;

    public function test_home_has_og_tags(): void
    // GET / -> assertSee('og:title')

    public function test_home_has_schema_org(): void
    // GET / -> assertSee('application/ld+json') + assertSee('Museum')

    public function test_sitemap_returns_xml(): void
    // GET /sitemap.xml -> 200, Content-Type: application/xml

    public function test_sitemap_contains_static_pages(): void
    public function test_sitemap_contains_published_excursions(): void
    public function test_sitemap_excludes_unpublished(): void

    public function test_robots_txt_returns_text(): void
    // GET /robots.txt -> 200, Content-Type: text/plain, contains 'Sitemap'

    public function test_robots_txt_uses_custom_from_settings(): void

    public function test_excursion_page_has_meta_description(): void
    public function test_article_page_has_og_image(): void

    public function test_analytics_scripts_rendered_when_configured(): void
    // Установить seo.analytics_yandex -> GET / -> assertSee('ym(')

    public function test_analytics_scripts_not_rendered_when_empty(): void
}
```

---

## Конфигурация и окружение

### .env.example (дополнить)

Без новых env-переменных -- аналитика и robots.txt хранятся в БД (таблица settings), управляются через админку.

### config/purify.php

Публикуется при `composer require stevebauman/purify`, настраивается как описано в начале документа.

### composer.json (итоговые зависимости)

```json
"require": {
    "php": "^8.2",
    "laravel/framework": "^11.0",
    "laravel/tinker": "^2.9",
    "stevebauman/purify": "^3.0",
    "phpoffice/phpword": "^1.0"
}
```

---

## Итоговая структура файлов (по фазам)

### Фаза 1

```
NEW  database/migrations/2026_04_01_000001_create_settings_table.php
NEW  app/Models/Setting.php
NEW  app/Http/Controllers/Admin/SettingsController.php
NEW  resources/views/admin/settings/edit.blade.php
NEW  resources/views/layouts/admin-auth.blade.php
NEW  database/seeders/SettingsSeeder.php
NEW  tests/Feature/Admin/SettingsTest.php
MOD  resources/views/layouts/admin.blade.php
MOD  resources/views/admin/dashboard.blade.php
MOD  resources/views/admin/login.blade.php (extends -> admin-auth)
MOD  resources/views/admin/register.blade.php (extends -> admin-auth)
MOD  resources/views/admin/verify.blade.php (extends -> admin-auth)
MOD  resources/views/pages/contacts.blade.php
MOD  resources/views/pages/about.blade.php
MOD  resources/views/components/modals.blade.php
MOD  public/css/admin.css
MOD  app/Providers/AppServiceProvider.php
MOD  routes/admin.php
MOD  database/seeders/DatabaseSeeder.php
```

### Фаза 2

```
NEW  database/migrations/2026_04_01_000002_create_news_table.php
NEW  app/Models/News.php
NEW  app/Services/ImageUploadService.php
NEW  app/Http/Controllers/Admin/NewsController.php
NEW  app/Http/Controllers/PageController.php
NEW  resources/views/admin/news/index.blade.php
NEW  resources/views/admin/news/create.blade.php
NEW  resources/views/admin/news/edit.blade.php
NEW  resources/views/admin/news/_form.blade.php
NEW  database/seeders/NewsSeeder.php
NEW  tests/Feature/Admin/NewsTest.php
NEW  tests/Feature/Public/NewsPageTest.php
MOD  resources/views/pages/news.blade.php
MOD  routes/web.php
MOD  routes/admin.php
MOD  resources/views/layouts/admin.blade.php (sidebar + Новости)
MOD  composer.json (+ stevebauman/purify)
MOD  config/purify.php (публикация + настройка)
```

### Фаза 3

```
NEW  database/migrations/2026_04_01_000003_create_excursions_table.php
NEW  app/Models/Excursion.php
NEW  app/Http/Controllers/Admin/ExcursionController.php
NEW  app/Http/Controllers/Admin/UploadController.php
NEW  resources/views/admin/excursions/index.blade.php
NEW  resources/views/admin/excursions/create.blade.php
NEW  resources/views/admin/excursions/edit.blade.php
NEW  resources/views/admin/excursions/_form.blade.php
NEW  resources/views/admin/partials/tinymce.blade.php
NEW  resources/views/pages/excursion-show.blade.php
NEW  database/seeders/ExcursionsSeeder.php
NEW  public/vendor/tinymce/ (self-hosted)
NEW  tests/Feature/Admin/ExcursionTest.php
NEW  tests/Feature/Public/ExcursionPageTest.php
MOD  resources/views/pages/excursions.blade.php
MOD  resources/views/pages/home.blade.php (секция экскурсий)
MOD  routes/web.php
MOD  routes/admin.php
MOD  resources/views/layouts/admin.blade.php (sidebar + Экскурсии)
DEL  resources/views/pages/excursion-overview.blade.php (после верификации)
DEL  resources/views/pages/excursion-junker.blade.php
DEL  resources/views/pages/excursion-awards.blade.php
DEL  resources/views/pages/excursion-topographic-service.blade.php
DEL  resources/views/pages/excursion-irkutsk-topographic.blade.php
DEL  resources/views/pages/excursion-documents.blade.php
```

### Фаза 4

```
NEW  database/migrations/2026_04_01_000004_create_articles_table.php
NEW  app/Models/Article.php
NEW  app/Http/Controllers/Admin/ArticleController.php
NEW  app/Services/ArticleImportService.php
NEW  resources/views/admin/articles/index.blade.php
NEW  resources/views/admin/articles/create.blade.php
NEW  resources/views/admin/articles/edit.blade.php
NEW  resources/views/admin/articles/_form.blade.php
NEW  resources/views/pages/article-show.blade.php
NEW  database/seeders/ArticlesSeeder.php
NEW  tests/Feature/Admin/ArticleTest.php
NEW  tests/Feature/Public/ArticlePageTest.php
MOD  resources/views/pages/home.blade.php (дерево формирований)
MOD  resources/views/components/header.blade.php (dropdown из БД)
MOD  public/js/main.js (data-nav-group вместо хардкода)
MOD  resources/views/layouts/app.blade.php (data-nav-group на body)
MOD  routes/web.php (+ /article/{slug}, + 301 редиректы)
MOD  routes/admin.php
MOD  resources/views/layouts/admin.blade.php (sidebar + Статьи)
MOD  app/Providers/AppServiceProvider.php (View Composer для header)
MOD  composer.json (+ phpoffice/phpword)
DEL  resources/views/pages/military-town.blade.php (после верификации)
DEL  resources/views/pages/junker-school.blade.php
DEL  resources/views/pages/infantry-courses.blade.php
DEL  resources/views/pages/topographic-unit.blade.php
```

### Фаза 5

```
NEW  database/migrations/2026_04_01_000005_create_catalog_items_table.php
NEW  app/Models/CatalogItem.php
NEW  app/Http/Controllers/Admin/CatalogController.php
NEW  resources/views/admin/catalog/index.blade.php
NEW  resources/views/admin/catalog/create.blade.php
NEW  resources/views/admin/catalog/edit.blade.php
NEW  resources/views/admin/catalog/_form.blade.php
NEW  database/seeders/CatalogSeeder.php
NEW  tests/Feature/Admin/CatalogTest.php
NEW  tests/Feature/Public/CatalogPageTest.php
MOD  resources/views/pages/exposition.blade.php
MOD  resources/views/pages/archive.blade.php
MOD  routes/admin.php
MOD  routes/web.php
MOD  resources/views/layouts/admin.blade.php (sidebar + Экспозиция, Архив)
```

### Фаза 6

```
MOD  app/Http/Controllers/Admin/SettingsController.php (таб "О музее" + WYSIWYG)
MOD  resources/views/admin/settings/edit.blade.php (+ таб О музее)
MOD  resources/views/pages/about.blade.php (контент из settings)
MOD  resources/views/pages/home.blade.php (полностью динамическая)
MOD  app/Http/Controllers/PageController.php (+ home())
MOD  routes/web.php
NEW  tests/Feature/Public/HomePageTest.php
```

### Фаза 7

```
NEW  app/Http/Controllers/Admin/ReorderController.php
NEW  app/Console/Commands/CleanOrphanedImages.php
NEW  app/Http/Middleware/CachePublicPages.php
NEW  tests/Feature/Admin/ReorderTest.php
MOD  app/Http/Controllers/Admin/DashboardController.php (счётчики)
MOD  resources/views/admin/dashboard.blade.php (счётчики + последние)
MOD  routes/admin.php (+ reorder)
MOD  routes/web.php (все через PageController)
MOD  resources/views/layouts/admin.blade.php (раскомментировать все sidebar-ссылки)
```

### Фаза SEO

```
NEW  resources/views/components/seo-meta.blade.php
NEW  app/Http/Controllers/SitemapController.php
NEW  resources/views/sitemap.blade.php
NEW  app/Http/Controllers/RobotsController.php
NEW  tests/Feature/SeoTest.php
MOD  resources/views/layouts/app.blade.php (SEO meta + analytics)
MOD  resources/views/pages/home.blade.php (schema.org Museum)
MOD  resources/views/pages/excursion-show.blade.php (schema.org Event)
MOD  resources/views/pages/article-show.blade.php (schema.org Article)
MOD  resources/views/pages/news.blade.php (meta description)
MOD  resources/views/admin/settings/edit.blade.php (таб SEO)
MOD  app/Http/Controllers/Admin/SettingsController.php (валидация SEO-полей)
MOD  routes/web.php (+ /sitemap.xml, /robots.txt)
```

---

## Итоговая схема БД (5 новых таблиц)

```
settings       (id, key, value, group, timestamps)
news           (id, title, text, image_path, published_at, is_published, sort_order, timestamps, deleted_at)
excursions     (id, slug, title, short_title, short_description, duration_minutes, group_size_min, group_size_max, description, what_you_see, interesting_facts, image_path, is_published, sort_order, meta_title, meta_description, timestamps, deleted_at)
articles       (id, slug, title, content, image_path, parent_id FK->articles, is_published, sort_order, meta_title, meta_description, timestamps, deleted_at)
catalog_items  (id, type, title, description, image_path, link_url, is_published, sort_order, timestamps)
```

## Внешние зависимости

| Пакет | Тип | Фаза | Назначение |
|-------|-----|------|------------|
| stevebauman/purify | composer | 1 | HTML-санитизация при записи в БД |
| phpoffice/phpword | composer | 4 | Импорт .docx в HTML |
| TinyMCE 6 community | self-hosted (public/vendor/) | 3 | WYSIWYG без CDN |
| SortableJS | CDN | 7 | Drag & drop сортировка |

## Deployment checklist

1. `composer install --no-dev --optimize-autoloader`
2. `php artisan key:generate` (если нет)
3. `php artisan storage:link`
4. `php artisan migrate`
5. `php artisan db:seed`
6. Убедиться что nginx/Apache запрещает выполнение PHP в `storage/`
7. `APP_DEBUG=false`, `LOG_LEVEL=warning`
8. Настроить SMTP для email
9. `upload_max_filesize >= 6M`, `post_max_size >= 8M` в php.ini
10. Проверить `php artisan test`
11. Настроить бэкап БД (cron mysqldump или spatie/laravel-backup)

---

## Исправления по итогам ревью (errata)

Ниже перечислены исправления к основному тексту плана, выявленные при двух раундах критического анализа.

### E1. Purify подключается в Фазе 1, не в Фазе 2
`stevebauman/purify` устанавливается в Фазе 1 вместе с settings, т.к. `modals.about` содержит HTML и выводится через `{!! !!}`. SettingsController::update() санитизирует HTML-поля (modals.about, а позже about.history, about.mission) через Purify при сохранении.

### E2. figcaption в Purify config: Block, не Inline
В custom_definition figcaption определён как `Block` (может содержать блочные элементы по HTML5).

### E3. Setting::set() проверяет ALLOWED_KEYS
Метод `Setting::set()` выбрасывает исключение для ключей, не входящих в whitelist.

### E4. Schema.org для экскурсий: TouristAttraction, не Event
Event требует обязательное поле `startDate` (конкретная дата). Экскурсии -- постоянные предложения. Использовать:
```json
{ "@type": "TouristAttraction", "name": "...", "description": "...", "provider": { "@type": "Museum", "name": "..." } }
```

### E5. CatalogItem link_url: запрет javascript: URI
Валидация в CatalogController:
```php
'link_url' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\//i'],
```
Это запрещает `javascript:`, `data:`, `vbscript:` и прочие опасные схемы.

### E6. Analytics IDs: экранирование в JS-контексте
Яндекс.Метрика ID выводить как `{{ (int) ($settings['seo.analytics_yandex'] ?? 0) }}`.
Google Analytics ID выводить как `{{ Js::from($settings['seo.analytics_google'] ?? '') }}`.

### E7. ImageUploadService: whitelist директорий
```php
private const ALLOWED_DIRS = ['news', 'excursions', 'articles', 'catalog', 'content', 'settings'];
public function upload(UploadedFile $file, string $directory): string
{
    if (!in_array($directory, self::ALLOWED_DIRS)) {
        throw new \InvalidArgumentException("Недопустимая директория: {$directory}");
    }
    // ...
}
```

### E8. robots.txt: не блокировать /storage/uploads/
Заменить `Disallow: /storage/` на `Disallow: /storage/logs/`. Изображения в `/storage/uploads/` должны индексироваться для SEO (og:image).

### E9. ReorderController: transaction + фильтрация по type
Обернуть цикл обновления sort_order в `DB::transaction()`. Для catalog-сущностей фильтровать по type: `CatalogItem::where('type', $type)->where('id', $id)->update(...)`.

### E10. formationsTree: null-check для military-town
```php
$militaryTown = Article::where('slug', 'military-town')->published()->first();
$formationsTree = $militaryTown
    ? collect([$militaryTown])->merge($militaryTown->children()->published()->ordered()->get())
    : collect();
```

### E11. Excursion short_title: санитизация при выводе
Вместо `{!! $excursion->short_title !!}` использовать `{!! nl2br(e($excursion->short_title)) !!}` или добавить мутатор, разрешающий только `<br>`.

### E12. 301 редиректы экскурсий -- не нужны
Slug-и в сидере совпадают с текущими URL-сегментами (`/excursion/overview` -> `/excursion/overview`). Формат URL не меняется, поэтому 301 редиректы для экскурсий не требуются. Нужны только для статей: `/military-town` -> `/article/military-town`.

### E13. Twitter Card мета-теги
Добавить в seo-meta.blade.php:
```html
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $fullTitle }}">
<meta name="twitter:description" content="{{ $metaDescription }}">
@if($ogImageUrl)<meta name="twitter:image" content="{{ $ogImageUrl }}">@endif
```

### E14. Мобильная навигация admin-панели
Добавить burger-кнопку для toggle sidebar на мобильных (< 768px). Sidebar по умолчанию скрыт, появляется по клику. CSS: `.admin-sidebar { display: none }` + `.admin-sidebar.is-open { display: flex }`. JS: toggle по кнопке.

### E15. Авто-генерация slug из title
В формах создания экскурсий и статей добавить JS-транслитерацию title -> slug (onInput). Библиотека не нужна: маппинг кириллицы в латиницу (30 строк JS). Slug доступен для ручной правки.

### E16. Удаление изображения без замены
Добавить в формы чекбокс "Удалить изображение" (`<input type="checkbox" name="remove_image">`). В контроллерах: если `remove_image` = true, удалить файл и обнулить image_path.

### E17. Кеширование navArticles в View Composer
```php
View::composer('components.header', function ($view) {
    $navArticles = Cache::remember('nav_articles', 300, function () { ... });
    $view->with('navArticles', $navArticles);
});
```
Инвалидация при обновлении articles: `Cache::forget('nav_articles')` в ArticleController::store/update/destroy.

### E18. Кеширование sitemap.xml
Обернуть генерацию в `Cache::remember('sitemap', 3600, ...)`. Инвалидировать при изменении контента.

### E19. Rate limiting на upload endpoint
`Route::post('/upload/image', ...)->middleware('throttle:30,1')` -- максимум 30 загрузок в минуту.

### E20. Верификация Яндекс.Вебмастер и Google Search Console
Добавить в settings: `seo.verification_yandex`, `seo.verification_google`.
В layouts/app.blade.php: `<meta name="yandex-verification" content="{{ $settings['seo.verification_yandex'] ?? '' }}">`.

### E21. Заголовок и подзаголовок музея в settings
Добавить ключи `general.site_title` и `general.site_subtitle` в settings. В header.blade.php читать из `$settings`.

### E22. noindex для admin-страниц
В layouts/admin.blade.php и admin-auth.blade.php добавить `<meta name="robots" content="noindex, nofollow">`.

### E23. Стилизованные страницы 404/500
Создать `resources/views/errors/404.blade.php` и `resources/views/errors/500.blade.php` с `@extends('layouts.app')`.

### E24. Client-side валидация размера файлов
JS на input[type=file]: проверять `file.size <= 5 * 1024 * 1024` перед отправкой. Показывать ошибку "Максимальный размер файла: 5 MB" и блокировать submit.

### E25. Alt-тексты для изображений
Добавить nullable поле `image_alt` (VARCHAR 255) в таблицы news, excursions, catalog_items. В admin-формах: input "Описание изображения". В публичных шаблонах: `alt="{{ $item->image_alt ?? $item->title }}"`.

### E26. Переименование таба "Модалки"
Таб в настройках переименовать в "Всплывающие окна" -- понятнее для нетехнического администратора.

# CMS для сайта музея "Иркутское юнкерское училище"

## Контекст
Весь контент сайта (17 страниц) захардкожен в Blade-шаблонах. Админка реализована только на уровне auth (2FA по email) с dashboard-заглушкой. Нужна CMS для управления контентом через admin-панель. Без внешних CMS-пакетов (Filament, Nova) -- пишем на Blade, расширяя существующий admin.css design system.

## Принципы
- Простота > гибкость (маленький музейный сайт, не CMS общего назначения)
- Минимум зависимостей
- Каждая фаза самодостаточна и приносит пользу
- Сидеры для миграции текущего хардкода в БД
- TinyMCE self-hosted (public/vendor/tinymce/) для rich-контента
- HTML-санитизация с первого дня (stevebauman/purify) -- не откладывать
- Все модели с явным `$fillable`, SoftDeletes для контентных сущностей
- Сидеры идемпотентные (updateOrCreate + DB::transaction)

## Сквозные правила (все фазы)
- **Санитизация HTML**: `stevebauman/purify` подключается в Фазе 2. HTML санитизируется при записи в БД (мутатор на модели). Конфиг Purify: разрешить HTML5-элементы (figure, figcaption) через custom_definition, запретить script/iframe/form/object/embed.
- **Загрузка файлов**: валидация `'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'`. Имя через `Str::uuid()`. Хранение в `storage/app/public/uploads/`.
- **Удаление записей**: JS confirm() на кнопку. Файлы image_path удалять только в `forceDeleting` event (не в `deleting`), чтобы SoftDeletes + restore не теряли изображения. Orphaned WYSIWYG-files -- Artisan-команда в фазе 7.
- **Сидеры**: `updateOrCreate()` + `DB::transaction()`. В articles сидере -- копировать только содержимое `<div class="article">...</div>`, без Blade-директив, без `<h2>`, без breadcrumbs.
- **$fillable**: явно перечислять для каждой модели. Никаких `$guarded = []`.
- **SoftDeletes**: для articles, excursions, news.
- **storage:link**: включить в deployment-скрипт.
- **Тесты**: минимальный набор Feature-тестов в каждой фазе (CRUD + валидация + auth redirect).

---

## Фаза 1: Admin layout + Настройки сайта
**Цель**: превратить заглушку в рабочую админку, вынести контакты/расписание/модалки в БД. Высшая ценность -- контактные данные дублируются в 3 местах (contacts, about, modals).

### БД
Таблица `settings`: id, key (string 191, unique), value (text nullable), group (string 50: contacts/schedule/modals/about/home), timestamps.

Ключи для сидера:
- `contacts.address`, `contacts.phone`, `contacts.email`, `contacts.map_id` (только ID конструктора Яндекс.Карт, `<script>` собирается в Blade)
- `schedule.weekdays`, `schedule.saturday`, `schedule.sunday`, `schedule.note`
- `modals.about` (HTML), `modals.location_address`

### Модель `Setting`
Статические хелперы: `Setting::get($key, $default)`, `Setting::set($key, $value)`, `Setting::getGroup($group)`.
Кеширование: все ключи загружаются одним запросом в `Cache::rememberForever('settings', fn() => self::pluck('value', 'key'))`. Сброс при set().
Whitelist допустимых ключей -- контроллер не принимает произвольные ключи. Только edit/update, без create/destroy (ключи создаются сидером).

### Admin layout рефакторинг
- Выделить `layouts/admin-auth.blade.php` (текущий центрированный card layout для login/register/verify)
- Переписать `layouts/admin.blade.php` в панель: sidebar с навигацией + main content area
- Auth-views переключить на admin-auth layout

### Глобальные настройки для публичных шаблонов
View Composer на `layouts.app` (не View::share) -- настройки не грузятся для admin-запросов и artisan-команд:
```php
View::composer('layouts.app', function ($view) {
    $view->with('settings', Setting::cached());
});
```

### Новые CSS-компоненты в admin.css
`.admin-sidebar`, `.admin-main`, `.admin-page-header`, `.admin-form` (group/label/input/textarea/select/button), `.admin-table`, `.admin-alert`, `.admin-card`.

### Файлы
```
NEW  migration create_settings_table
NEW  app/Models/Setting.php
NEW  app/Http/Controllers/Admin/SettingsController.php (edit, update -- без create/destroy)
NEW  resources/views/admin/settings/edit.blade.php (форма с группировкой по табам)
NEW  resources/views/layouts/admin-auth.blade.php
NEW  database/seeders/SettingsSeeder.php (updateOrCreate + DB::transaction)
MOD  resources/views/layouts/admin.blade.php -> panel layout
MOD  admin/login,register,verify -> extends admin-auth
MOD  admin/dashboard.blade.php -> реальный dashboard с quick links
MOD  pages/contacts.blade.php -> из БД
MOD  pages/about.blade.php -> расписание из БД
MOD  components/modals.blade.php -> из БД
MOD  public/css/admin.css -> sidebar, form, table компоненты
MOD  app/Providers/AppServiceProvider.php -> View::composer для layouts.app
MOD  routes/admin.php -> GET/PUT /admin/settings
```

---

## Фаза 2: Новости (CRUD + загрузка изображений + санитизация)
**Цель**: самый динамичный контент. CRUD с изображениями. Подключение HTML-санитизации.

### Зависимость
`composer require stevebauman/purify` -- HTML-санитизация (активно поддерживается, Laravel 11+).

### БД
Таблица `news`: id, title (string 255), text (text), image_path (string 255 nullable), published_at (date, index), is_published (bool default true), sort_order (uint default 0), timestamps, deleted_at (softDeletes).
Составной индекс: (is_published, published_at desc).

### Модель `News`
Traits: HasFactory, SoftDeletes. Явный $fillable.
Scopes: published(). Accessors: image_url, formatted_date.
Boot: deleting event -> удалить файл image_path через ImageUploadService.

### ImageUploadService (переиспользуется во всех фазах)
`upload(UploadedFile, directory): string` -- Str::uuid() имя, сохраняет в storage/app/public/uploads/{dir}/.
`delete(?path): void`. `replace(?oldPath, file, dir): string`.
Валидация: `'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'`.
Требуется `php artisan storage:link`.

### Admin CRUD
NewsController: index (paginated), create, store, edit, update, destroy.
Views: index (таблица), create/edit (форма через _form partial).
Форма: title (text), published_at (date), text (textarea, без WYSIWYG -- короткие тексты), image (file + preview), is_published (checkbox).
Кнопка удаления: JS confirm("Удалить новость '{title}'?").

### Публичная часть
Новый `PageController` (будет расти в каждой фазе).
`routes/web.php`: `/news` -> `PageController::news()` с `->paginate(12)`.
`pages/news.blade.php` -> цикл по `$news` + пагинация.

### Файлы
```
NEW  migration create_news_table
NEW  app/Models/News.php (SoftDeletes)
NEW  app/Services/ImageUploadService.php
NEW  app/Http/Controllers/Admin/NewsController.php
NEW  app/Http/Controllers/PageController.php
NEW  resources/views/admin/news/{index,create,edit,_form}.blade.php
NEW  database/seeders/NewsSeeder.php (10 новостей, updateOrCreate)
MOD  pages/news.blade.php -> динамический + пагинация
MOD  routes/web.php -> news через PageController
MOD  routes/admin.php -> Route::resource('news', ...)
MOD  admin layout sidebar -> + ссылка "Новости"
MOD  composer.json -> require stevebauman/purify
```

---

## Фаза 3: Экскурсии (CRUD + TinyMCE)
**Цель**: 6 экскурсий со структурированными данными + WYSIWYG для описаний. Первое внедрение TinyMCE.

### БД
Таблица `excursions`: id, slug (string 191 unique), title (string 255), short_title (string 100 nullable -- для кнопок на главной, допускает HTML-entities), short_description (text), duration_minutes (unsignedSmallInt), group_size_min (unsignedSmallInt default 5), group_size_max (unsignedSmallInt default 25), description (text -- WYSIWYG HTML), what_you_see (text nullable -- WYSIWYG), interesting_facts (text nullable -- WYSIWYG), image_path (string 255 nullable), is_published (bool default true), sort_order (uint default 0), timestamps, deleted_at (softDeletes).
Индексы: unique(slug), index(is_published, sort_order).
SEO-поля: meta_title (string 255 nullable), meta_description (string 500 nullable).

### TinyMCE -- self-hosted
Скачать TinyMCE 6 community в `public/vendor/tinymce/`. Без CDN-зависимости, без API-ключа, без баннера.
Инициализация на textarea.wysiwyg.
Безопасная конфигурация:
```js
valid_elements: 'p,br,strong/b,em/i,u,h2,h3,h4,ul,ol,li,a[href|target],img[src|alt|width|height|loading],blockquote,table,thead,tbody,tr,td,th,figure[class],figcaption',
invalid_elements: 'script,iframe,form,input,object,embed',
paste_word_valid_elements: 'p,b,strong,i,em,u,h2,h3,h4,ul,ol,li,a[href],table,tr,td,th',
```
WYSIWYG-контент санитизируется через Purify при записи в БД (мутатор на модели).

### UploadController для WYSIWYG-изображений
`POST /admin/upload/image` -> валидация + сохранение в uploads/content/.
CSRF-токен передаётся через заголовок X-CSRF-TOKEN в custom images_upload_handler.

### Публичная часть
Один шаблон `excursion-show.blade.php` вместо 6 файлов.
`/excursion/{excursion:slug}` -> `PageController::excursionShow()`.
Slug-и в сидере совпадают с текущими URL-сегментами: overview, junker, awards, topographic-service, irkutsk-topographic, documents.
Удалять 6 старых файлов только ПОСЛЕ верификации контента в БД.

### Файлы
```
NEW  migration create_excursions_table
NEW  app/Models/Excursion.php (SoftDeletes)
NEW  app/Http/Controllers/Admin/ExcursionController.php
NEW  app/Http/Controllers/Admin/UploadController.php
NEW  resources/views/admin/excursions/{index,create,edit,_form}.blade.php
NEW  resources/views/pages/excursion-show.blade.php
NEW  database/seeders/ExcursionsSeeder.php
NEW  public/vendor/tinymce/ (self-hosted TinyMCE 6)
DEL  pages/excursion-{overview,junker,awards,topographic-service,irkutsk-topographic,documents}.blade.php (после верификации)
MOD  pages/excursions.blade.php -> динамический
MOD  pages/home.blade.php -> кнопки экскурсий из БД
MOD  routes/web.php -> excursion routes через PageController
MOD  routes/admin.php -> resource + upload route
MOD  admin layout -> + ссылка "Экскурсии", подключение TinyMCE
```

---

## Фаза 4: Исторические статьи (rich-контент + импорт docx)
**Цель**: 4 статьи с rich HTML (figures, figcaption, внешние изображения, источники).

### БД
Таблица `articles`: id, slug (string 191 unique), title (string 255), content (longText -- полный WYSIWYG HTML), image_path (string 255 nullable), parent_id (unsignedBigInt nullable, FK -> articles.id ON DELETE SET NULL), is_published (bool default true), sort_order (uint default 0), meta_title (string 255 nullable), meta_description (string 500 nullable), timestamps, deleted_at (softDeletes).
Индексы: unique(slug), index(parent_id), index(is_published, sort_order).

### Почему одно поле `content` (longText)
Статьи содержат сложный HTML: h3, figure+figcaption, внешние img (Wikimedia), списки ссылок. Дробить на структурированные поля -- overengineering. WYSIWYG + HTML source editing покрывает все потребности.

### parent_id вместо parent_slug
FK constraint с ON DELETE SET NULL. Breadcrumbs через eager-loaded parent relationship. Не зависит от стабильности slug.

### TinyMCE для статей -- расширенная конфигурация
Добавить плагины: code (HTML source), table. Разрешить figure, figcaption через extended_valid_elements.

### Импорт из docx
Музей получает статьи в формате Word. Только `.docx` (не `.doc` -- бинарный формат плохо конвертируется).

**Реализация**: `composer require phpoffice/phpword`.
- Кнопка "Импорт из Word" в форме создания/редактирования статьи
- `POST /admin/articles/import` -> AJAX, возвращает HTML для вставки в TinyMCE
- Постобработка: очистка Word-мусора (mso-стили, пустые span), извлечение изображений из docx через ZipArchive, сохранение через ImageUploadService, замена ссылок в HTML
- Ограничение размера: max 10MB
- Предупреждение в UI: "Проверьте результат импорта, возможна потеря форматирования"
- Результат вставляется в TinyMCE для ручной правки перед сохранением

### Роутинг публичной части
Префикс `/article/{article:slug}` -- исключает конфликт с другими роутами, допускает любые slug без whitelist, не требует передеплоя при добавлении новой статьи через CMS.
Редиректы 301 со старых URL: `/military-town` -> `/article/military-town` и т.д.
Один шаблон `article-show.blade.php` вместо 4 файлов.
Удалять 4 старых файла только ПОСЛЕ верификации контента в БД.

### Файлы
```
NEW  migration create_articles_table
NEW  app/Models/Article.php (SoftDeletes, parent_id FK)
NEW  app/Http/Controllers/Admin/ArticleController.php
NEW  app/Services/ArticleImportService.php (конвертация docx -> HTML)
NEW  resources/views/admin/articles/{index,create,edit,_form}.blade.php
NEW  resources/views/pages/article-show.blade.php
NEW  database/seeders/ArticlesSeeder.php (HTML из текущих 4 шаблонов)
DEL  pages/{military-town,junker-school,infantry-courses,topographic-unit}.blade.php (после верификации)
MOD  pages/home.blade.php -> дерево формирований из БД
MOD  resources/views/components/header.blade.php -> dropdown "Военный городок" из БД (articles с parent_id)
MOD  public/js/main.js -> заменить хардкод route names на data-nav-group атрибут с body
MOD  routes/web.php -> /article/{slug} + Route::redirect 301 для 4 старых URL
MOD  routes/admin.php -> resource + import route
MOD  composer.json -> require phpoffice/phpword
```

---

## Фаза 5: Экспозиция + Архив (карточки)
**Цель**: 6+6 карточек с одинаковой структурой.

### БД
Одна таблица `catalog_items` с полем `type` (enum: exposition/archive): id, type (string 20, index), title (string 255), description (text), image_path (string 255 nullable), link_url (string 255 nullable), is_published (bool default true), sort_order (uint default 0), timestamps.
Составной индекс: (type, is_published, sort_order).

### Модель `CatalogItem`
Scope: `scopeExposition()`, `scopeArchive()`, `scopePublished()`.
Один контроллер `CatalogController` с параметром type. Один набор views с условным заголовком.

### Простые CRUD без WYSIWYG
Описания короткие (1-2 предложения) -- обычная textarea.

### Файлы
```
NEW  migration create_catalog_items_table
NEW  app/Models/CatalogItem.php
NEW  app/Http/Controllers/Admin/CatalogController.php
NEW  resources/views/admin/catalog/{index,create,edit,_form}.blade.php
NEW  database/seeders/CatalogSeeder.php (6 exposition + 6 archive)
MOD  pages/{exposition,archive}.blade.php -> динамические
MOD  routes/admin.php -> catalog routes с параметром type
MOD  routes/web.php -> через PageController
```

---

## Фаза 6: О музее + Главная
**Цель**: оставшиеся страницы.

### О музее
Через settings (группа `about`): `about.history` (WYSIWYG HTML), `about.mission` (WYSIWYG HTML). Расписание уже в БД из фазы 1.
Добавить вкладку "О музее" в SettingsController с TinyMCE-полями.
HTML санитизируется при сохранении.

### Главная
После фаз 3-5 главная уже частично динамическая (экскурсии, формирования из articles). Оставшееся: центральное изображение -> `home.building_image` в settings (загрузка файла).

---

## Фаза 7: Полировка
- **Drag & drop сортировка**: SortableJS через CDN для catalog/excursions. AJAX endpoint `POST /admin/{entity}/reorder`
- **Все routes через PageController**: убрать оставшиеся замыкания из web.php
- **Dashboard**: счётчики (новости, экскурсии, статьи и т.д.) + последние добавленные
- **Кеширование**: Settings уже кешируются (фаза 1). Добавить кеш для публичных страниц, сброс при CRUD-операциях
- **Orphaned WYSIWYG images**: Artisan-команда для поиска файлов в uploads/content/, не упоминаемых ни в одном HTML-контенте

---

## Итоговая схема БД (5 новых таблиц)
```
settings       -- key-value настройки сайта
news           -- новости с датами и изображениями (SoftDeletes)
excursions     -- экскурсии со структурированными данными + WYSIWYG (SoftDeletes)
articles       -- исторические статьи, rich HTML, импорт из Word (SoftDeletes, self-ref parent_id)
catalog_items  -- карточки экспозиции и архива (type: exposition/archive)
```

## Внешние зависимости (добавляются)
- `stevebauman/purify` (composer, Фаза 2) -- HTML-санитизация при записи в БД
- `phpoffice/phpword` (composer, Фаза 4) -- импорт docx в HTML
- TinyMCE 6 community (self-hosted в public/vendor/tinymce/, Фаза 3) -- WYSIWYG без CDN-зависимости
- SortableJS (CDN, Фаза 7) -- drag & drop сортировка

## Deployment checklist
1. `composer install`
2. `php artisan storage:link`
3. `php artisan migrate`
4. `php artisan db:seed`
5. Убедиться что nginx/Apache запрещает выполнение PHP в `storage/`

## Проверка (после каждой фазы)
1. `php artisan migrate` -- миграции без ошибок
2. `php artisan db:seed --class=XxxSeeder` -- данные перенесены, повторный запуск идемпотентен
3. Публичные страницы отображаются идентично текущим (визуальная регрессия)
4. Admin CRUD: создание/редактирование/удаление работает
5. Загрузка изображений: файлы в storage, отображаются на сайте
6. Удаление записи: файл удаляется с диска, SoftDeletes работает
7. HTML-санитизация: `<script>` и `<iframe>` не сохраняются в БД
8. `php artisan test` -- существующие тесты проходят

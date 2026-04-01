---
name: architecture
description: Структура директорий, роуты, контроллеры, middleware, конфигурация, паттерны
type: project
---
# Архитектура
## Тип приложения
Информационный сайт на Laravel 11.48 с админ-панелью. Контент: настройки из БД (таблица settings), новости из БД (таблица news), экскурсии из БД (таблица excursions), статьи из БД (таблица articles, иерархия). Админка: 2FA по email, dashboard, CMS настроек (Фаза 1), News CRUD (Фаза 2), Excursions CRUD с TinyMCE WYSIWYG (Фаза 3), Articles CRUD с импортом DOCX (Фаза 4), Catalog CRUD (Фаза 5), About+Home через settings (Фаза 6).
## Структура директорий
```
museum/
  server/                        # Laravel-приложение
    app/
      Http/
        Controllers/
          Controller.php         # Базовый (пуст)
          PageController.php     # Публичные страницы (home, about, news, excursions, excursionShow, articleShow, exposition, archive)
          Admin/
            AuthController.php   # Login, verify, resend, logout (192 строки)
            RegisterController.php # Register (53 строки)
            DashboardController.php # Dashboard (17 строк)
            SettingsController.php # Редактирование настроек (edit/update)
            NewsController.php   # News CRUD (resource controller)
            ExcursionController.php # Excursions CRUD (resource controller)
            ArticleController.php # Articles CRUD + import() (resource controller)
            UploadController.php # Загрузка изображений из WYSIWYG
        Middleware/
          CheckRegistrationEnabled.php  # 404 если ADMIN_REGISTER_ENABLE=false
          EnsureVerificationPending.php # Redirect если нет pending_user_id в сессии
      Mail/
        VerificationCodeMail.php # Email с кодом подтверждения
      Models/
        User.php                 # Стандартная + verificationCodes()
        VerificationCode.php     # Коды 2FA (72 строки)
        News.php                 # Новости (SoftDeletes, HasFactory)
        Excursion.php            # Экскурсии (SoftDeletes, HasFactory, Purify mutators)
        Article.php              # Статьи (SoftDeletes, HasFactory, иерархия parent/children, Purify setContentAttribute)
      Services/
        VerificationCodeService.php # Генерация/проверка/cleanup кодов
        ImageUploadService.php      # Загрузка/удаление/замена изображений
        ArticleImportService.php    # Импорт DOCX→HTML (phpoffice/phpword)
      Providers/
        AppServiceProvider.php   # View Composer: siteSettings (layouts.app, pages.*, components.*) + navArticles (кешируется) для header
    bootstrap/
      app.php                    # Middleware aliases, routing config
      providers.php              # Только AppServiceProvider
    config/
      app.php                    # admin_register_enable => env('ADMIN_REGISTER_ENABLE', false)
      purify.php                 # HTMLPurifier конфиг (stevebauman/purify)
      auth.php                   # Guard: web (session), Provider: users (Eloquent)
      database.php               # Default: sqlite
      logging.php                # Default: stack -> single, level: debug
      mail.php                   # Default: log
      session.php                # Driver: database, lifetime: 120 мин
      cache.php                  # Store: database
    database/migrations/         # 6 миграций (см. models.md)
    database/seeders/            # DatabaseSeeder -> SettingsSeeder, AboutSeeder, NewsSeeder, ExcursionsSeeder, ArticlesSeeder, CatalogSeeder
    database/factories/          # UserFactory, NewsFactory, ExcursionFactory
    resources/views/
      layouts/app.blade.php      # Публичный layout (25 строк)
      layouts/admin.blade.php    # Admin panel layout с sidebar (авторизованные)
      layouts/admin-auth.blade.php # Admin auth layout (login/register/verify)
      components/                # header, footer, breadcrumbs, modals
      pages/                     # 12 контентных страниц (6 excursion-* удалены, заменены на excursion-show + excursions из БД)
      admin/                     # login, register, verify, dashboard, settings/edit, news/*, excursions/*, partials/tinymce
      emails/                    # verification-code.blade.php
    resources/views/
      admin/articles/            # index, create, edit, _form (TinyMCE + DOCX-импорт AJAX)
      pages/article-show.blade.php # Детальная страница статьи (breadcrumbs, children list)
      pages/military-town, junker-school, infantry-courses, topographic-unit — УДАЛЕНЫ (заменены БД)
    routes/
      web.php                    # Публичные роуты + /article/{article:slug} + 301 редиректы старых URL
      admin.php                  # Admin роуты + resource articles + POST /articles/import
      console.php                # inspire (заглушка)
    public/
      css/style.css              # 1359 строк, BEM
      css/admin.css              # ~650 строк (auth + panel layout + forms/tabs/table/card/alert)
      js/main.js                 # 184 строки
      images/                    # 5 файлов (anfas.jpg 7.2MB, ivu.jpg, uu.jpg, znak-ivu.jpg, znam-irk-obl.jpg)
    public/vendor/tinymce/       # TinyMCE 6 self-hosted (tinymce.min.js, plugins, skins, langs/ru.js)
    tests/
      Feature/ExampleTest.php    # GET / -> 200
      Feature/NewsControllerTest.php  # 8 тестов (auth, CRUD, public, image)
      Feature/ExcursionControllerTest.php # 9 тестов (auth, CRUD, slug unique, public, 404, upload)
      Feature/ArticleControllerTest.php # 11 тестов (auth, CRUD, slug unique, public, 404, breadcrumbs, redirects, import)
      Feature/CatalogControllerTest.php # 9 тестов (auth, CRUD, exposition/archive, link url protocol, 404)
      Feature/SettingsControllerTest.php # 8 тестов (auth, edit, update, sanitize HTML, about page, home page, upload/remove building image)
      Unit/ExampleTest.php       # true === true
  frontend/                      # Статический HTML-прототип (до Laravel)
```
## Маршрутизация
### web.php — публичные роуты
`/` (home), `/news`, `/exposition`, `/archive`, `/about`, `/contacts`, `/excursions`, `/excursion/{excursion:slug}`, `/article/{article:slug}` (articleShow).
301-редиректы: `/military-town` → `/article/military-town`, `/junker-school`, `/infantry-courses`, `/topographic-unit` аналогично.
### admin.php — роуты, prefix `/admin`, name prefix `admin.`
Guest: `GET/POST login`, `GET/POST register` (+middleware registration.enabled)
Verification: `GET/POST verify`, `POST verify/resend` (middleware verification.pending)
Auth: `GET /` (dashboard), `POST logout`, `GET/PUT /settings`, resource `news`, resource `excursions`, resource `articles`, `POST /articles/import`, `POST /upload/image` (throttle:30,1)
## Middleware
### Стандартные (Laravel)
- `web` — CSRF, session, cookies (все роуты)
- `guest` — только для неавторизованных (redirect на admin.dashboard)
- `auth` — только для авторизованных (redirect на admin.login)
### Кастомные (bootstrap/app.php:20-26)
- `verification.pending` => EnsureVerificationPending — проверяет `pending_user_id` в сессии
- `registration.enabled` => CheckRegistrationEnabled — проверяет config `app.admin_register_enable`
- `redirectGuestsTo('/admin/login')` — глобальный redirect для auth middleware
## Паттерны
- MVC + Service Layer (VerificationCodeService)
- Валидация в контроллерах (Request::validate), без FormRequest
- DI через constructor injection (auto-wiring)
- Нет Policies/Gates — стандартная session-based auth
- Нет Repositories, Actions, Jobs, Events, Listeners, Notifications, Commands
## Конвенции именования
- Роуты: kebab-case, именованные
- Blade pages/: kebab-case
- CSS: BEM
- JS: camelCase
- Controllers: PascalCase, Admin/ namespace
## Конфигурация (.editorconfig)
charset: utf-8, end_of_line: lf, indent: 4 spaces, trim trailing whitespace, final newline. YAML: 2 spaces.
## Инструменты качества
- Laravel Pint (code style fixer) — установлен, без конфигурации
- PHPUnit 10.5 — 2 заглушки тестов
- phpstan/psalm/eslint/prettier/phpcs — не установлены

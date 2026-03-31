---
name: architecture
description: Структура директорий, роуты, контроллеры, middleware, конфигурация, паттерны
type: project
---
# Архитектура
## Тип приложения
Информационный сайт на Laravel 11.48 с админ-панелью. Контент захардкожен в Blade. Админка: 2FA по email, dashboard-заглушка.
## Структура директорий
```
museum/
  server/                        # Laravel-приложение
    app/
      Http/
        Controllers/
          Controller.php         # Базовый (пуст)
          Admin/
            AuthController.php   # Login, verify, resend, logout (192 строки)
            RegisterController.php # Register (53 строки)
            DashboardController.php # Dashboard (17 строк)
        Middleware/
          CheckRegistrationEnabled.php  # 404 если ADMIN_REGISTER_ENABLE=false
          EnsureVerificationPending.php # Redirect если нет pending_user_id в сессии
      Mail/
        VerificationCodeMail.php # Email с кодом подтверждения
      Models/
        User.php                 # Стандартная + verificationCodes()
        VerificationCode.php     # Коды 2FA (72 строки)
      Services/
        VerificationCodeService.php # Генерация/проверка/cleanup кодов
      Providers/
        AppServiceProvider.php   # Пустой
    bootstrap/
      app.php                    # Middleware aliases, routing config
      providers.php              # Только AppServiceProvider
    config/
      app.php                    # admin_register_enable => env('ADMIN_REGISTER_ENABLE', false)
      auth.php                   # Guard: web (session), Provider: users (Eloquent)
      database.php               # Default: sqlite
      logging.php                # Default: stack -> single, level: debug
      mail.php                   # Default: log
      session.php                # Driver: database, lifetime: 120 мин
      cache.php                  # Store: database
    database/migrations/         # 4 миграции (см. models.md)
    database/seeders/            # 1 тестовый пользователь
    database/factories/          # UserFactory
    resources/views/
      layouts/app.blade.php      # Публичный layout (25 строк)
      layouts/admin.blade.php    # Admin layout (17 строк)
      components/                # header, footer, breadcrumbs, modals
      pages/                     # 17 контентных страниц
      admin/                     # login, register, verify, dashboard
      emails/                    # verification-code.blade.php
    routes/
      web.php                    # 17 публичных GET-роутов
      admin.php                  # 9 admin-роутов
      console.php                # inspire (заглушка)
    public/
      css/style.css              # 1359 строк, BEM
      css/admin.css              # 339 строк
      js/main.js                 # 184 строки
      images/                    # 5 файлов (anfas.jpg 7.2MB, ivu.jpg, uu.jpg, znak-ivu.jpg, znam-irk-obl.jpg)
    tests/
      Feature/ExampleTest.php    # GET / -> 200
      Unit/ExampleTest.php       # true === true
  frontend/                      # Статический HTML-прототип (до Laravel)
```
## Маршрутизация
### web.php — 17 GET-роутов (все замыкания -> view)
Основные: `/`, `/news`, `/exposition`, `/archive`, `/about`, `/contacts`, `/excursions`
Военный городок: `/military-town`, `/junker-school`, `/infantry-courses`, `/topographic-unit`
Экскурсии: `/excursion/{overview,junker,awards,topographic-service,irkutsk-topographic,documents}`
### admin.php — 9 роутов, prefix `/admin`, name prefix `admin.`
Guest: `GET/POST login`, `GET/POST register` (+middleware registration.enabled)
Verification: `GET/POST verify`, `POST verify/resend` (middleware verification.pending)
Auth: `GET /` (dashboard), `POST logout`
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

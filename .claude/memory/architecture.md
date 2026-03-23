---
name: architecture
description: Маршруты, шаблоны, структура директорий, паттерны проекта museum
type: project
---
# Архитектура
## Тип приложения
Информационный сайт на Laravel 11 с админ-панелью. Контент в Blade-шаблонах. Админка: 2FA по email.
## Маршрутизация
`server/routes/web.php` — 24 GET-роута. Все именованные. Группы:
- Основные: `/`, `/news`, `/exposition`, `/archive`, `/about`, `/contacts`, `/excursions`
- Военный городок: `/military-town`, `/junker-school`, `/infantry-courses`, `/topographic-unit`
- Экскурсии: `/excursion/overview`, `/excursion/junker`, `/excursion/awards`, `/excursion/topographic-service`, `/excursion/irkutsk-topographic`, `/excursion/documents`
- Health check: `/up`
### Админка (`server/routes/admin.php`) — 9 роутов, prefix `admin.`:
- Guest: GET/POST `/admin/login`, GET/POST `/admin/register` (+ middleware registration.enabled)
- Verification: GET/POST `/admin/verify`, POST `/admin/verify/resend` (middleware verification.pending)
- Auth: POST `/admin/logout`, GET `/admin` (dashboard)
## Шаблоны (Blade)
Layout: `resources/views/layouts/app.blade.php`
- Секции: `title`, `content`, `modals`, стек `scripts`
- Подключает `public/css/style.css` и `public/js/main.js` через `asset()`
- `data-page` атрибут на body для JS
Admin layout: `resources/views/layouts/admin.blade.php` — подключает `public/css/admin.css`
Admin views: `resources/views/admin/{login,register,verify,dashboard}.blade.php`
Email: `resources/views/emails/verification-code.blade.php`
Компоненты (`resources/views/components/`): header, footer, breadcrumbs, modals
Страницы (`resources/views/pages/`): 15 файлов, все `@extends('layouts.app')`.
## Структура директорий
```
server/
  app/
    Http/Controllers/Controller.php
    Http/Controllers/Admin/AuthController.php
    Http/Controllers/Admin/RegisterController.php
    Http/Controllers/Admin/DashboardController.php
    Http/Middleware/EnsureVerificationPending.php
    Http/Middleware/CheckRegistrationEnabled.php
    Mail/VerificationCodeMail.php
    Models/User.php
    Models/VerificationCode.php
    Services/VerificationCodeService.php
    Providers/AppServiceProvider.php
  bootstrap/app.php
  config/
  database/migrations/
  resources/views/
  routes/web.php, admin.php, console.php
  public/css/style.css, admin.css
  public/js/main.js
  public/images/
```
## Паттерны
- BEM в CSS, CSS Custom Properties
- Адаптивная верстка: 1024px, 768px, 500px
- Vanilla JS: модули-функции (initActiveNavLink, initBurgerMenu, initDropdown, initModals, initFormationsAccordion)
- Контент в шаблонах (hardcoded)
## Конвенции именования
- Роуты: kebab-case
- Blade-файлы: kebab-case в pages/
- CSS-классы: BEM
- JS-функции: camelCase
## Middleware (custom)
- `verification.pending` => EnsureVerificationPending
- `registration.enabled` => CheckRegistrationEnabled
## Конфигурация
- `config/app.php`: `admin_register_enable` => env('ADMIN_REGISTER_ENABLE', false)

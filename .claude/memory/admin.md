---
name: admin
description: Админ-панель: auth 2FA, контроллеры, views, middleware, CSS, безопасность
type: project
---
# Админ-панель
## Статус
Auth-система полностью реализована. Dashboard — заглушка (только имя/email пользователя и logout).
## Контроллеры
- `AuthController` (193 строки) — login, verify, resend, logout. Rate limiting: 5 попыток/мин по IP+email.
- `RegisterController` (54 строки) — регистрация (опциональная, управляется флагом).
- `DashboardController` (18 строк) — возвращает view с auth user.
## Views
- `layouts/admin.blade.php` — layout, подключает admin.css
- `admin/login.blade.php` — форма входа
- `admin/register.blade.php` — форма регистрации
- `admin/verify.blade.php` — форма ввода кода с таймером
- `admin/dashboard.blade.php` — дашборд (30 строк)
- `emails/verification-code.blade.php` — email с кодом
## CSS
`public/css/admin.css` — 338 строк, BEM, design system (переменные цветов, радиусы, тени).
Компоненты: `.auth-card`, `.auth-form`, `.auth-alert`, `.dashboard`.
Layout: центрированный контейнер max-width 440px. Breakpoint 480px.
## Middleware
- `CheckRegistrationEnabled` — проверяет config('app.admin_register_enable'), возвращает 404
- `EnsureVerificationPending` — проверяет pending_user_id в сессии
## Сервисы
`VerificationCodeService` — generate (6 цифр, хеш, 10 мин), verify (проверка + инкремент попыток), cleanup.
## Безопасность
Password hashing, CSRF, rate limiting, лимит попыток кода (5), код 10 мин, Hash::check(), регенерация сессии.
## Маршруты (9)
Guest: GET/POST login, GET/POST register. Verification: GET/POST verify, POST verify/resend. Auth: GET dashboard, POST logout.
## Требует реализации
- Интерфейс управления контентом
- Управление пользователями
- Профиль / смена пароля
- Роли и права доступа

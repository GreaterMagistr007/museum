---
name: admin
description: Auth flow (2FA по email), rate limiting, безопасность, views, требования к развитию
type: project
---
# Админ-панель
## Статус
Auth-система реализована полностью. Dashboard — заглушка (имя/email/logout). CMS-функционал отсутствует.
## Auth Flow
1. POST /admin/login -> валидация email+password -> Auth::attempt -> сразу logout -> сессия pending_user_id -> генерация 6-значного кода -> email -> redirect /admin/verify
2. POST /admin/verify -> валидация code (size:6) -> VerificationCodeService::verify -> Auth::login -> session regenerate -> redirect /admin
3. POST /admin/verify/resend -> rate limit 1/60сек -> новый код -> email
4. POST /admin/logout -> Auth::logout -> invalidate session -> redirect /admin/login
5. POST /admin/register (если ADMIN_REGISTER_ENABLE=true) -> валидация name/email/password(confirmed) -> User::create -> сессия pending_user_id -> код registration -> email -> redirect /admin/verify
## Контроллеры
- AuthController (app/Http/Controllers/Admin/AuthController.php, 192 строки) — login, showLoginForm, verify, showVerifyForm, resendCode, logout, maskEmail (private).
- RegisterController (app/Http/Controllers/Admin/RegisterController.php, 53 строки) — register, showRegisterForm. Оба инжектят VerificationCodeService.
- DashboardController (app/Http/Controllers/Admin/DashboardController.php, 17 строк) — index().
## Rate Limiting
- Login: 5 попыток/мин, ключ `Str::transliterate(email)|IP` (AuthController:42-54).
- Resend: 1/60сек, ключ `resend-code|pending_user_id` (AuthController:144-150).
## Безопасность
- Пароли: автохеш через cast 'hashed' в User model
- Коды: Hash::make/Hash::check, 10 мин TTL, max 5 попыток
- CSRF: стандартный web middleware
- Session: regenerate после verify, invalidate при logout
- Email маскирование: "jo***@mail.ru" (AuthController:184-191)
- Регистрация: отключена по умолчанию (ADMIN_REGISTER_ENABLE=false)
## Views
- login.blade.php (67) — email+password, flash errors, link на register
- register.blade.php (78) — name+email+password+confirmation, link на login
- verify.blade.php (118) — input code (6 цифр, numeric), resend с таймером 60сек (inline JS + localStorage), flash messages
- dashboard.blade.php (30) — имя/email пользователя, кнопка logout
## Проблемы (выявлено 2026-03-31)
### Перед production
- Нет try-catch на Mail::send() — при ошибке отправки пользователь застревает в состоянии "ожидает верификации" (AuthController:72-73, RegisterController:47-48)
- Email отправляется синхронно (Mail::send) — нужно Mail::queue() для масштабирования
- cleanup() не автоматизирован — нет scheduler, просроченные коды копятся в БД
- При регистрации User создаётся ДО верификации кода (RegisterController:39-43) — нет очистки неверифицированных аккаунтов
- Нет логирования неудачных попыток входа (аудит безопасности)
- MAIL_MAILER=log в .env — в production нужен smtp
- 0 тестов для всей auth-системы
### Мелкие замечания
- Email маскирование показывает только 2 символа (AuthController:184-191) — может быть неинформативно
- login.blade.php содержит ссылку на register без проверки ADMIN_REGISTER_ENABLE на фронте (ссылка видна всегда, 404 при клике если выключено)
## Требует реализации
- Управление контентом (CMS)
- Управление пользователями
- Профиль / смена пароля
- Роли и права доступа (Policies/Gates)

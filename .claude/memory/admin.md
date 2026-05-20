---
name: admin
description: Auth flow (2FA по email), rate limiting, безопасность, views, требования к развитию
type: project
---
# Админ-панель
## Статус
Auth-система реализована полностью. Dashboard с quick links. CMS Фаза 1: настройки сайта. CMS Фаза 2: News CRUD. CMS Фаза 3: Excursions CRUD с TinyMCE WYSIWYG, загрузка изображений из редактора (UploadController), soft delete. CMS Фаза 4: Articles CRUD с импортом DOCX. CMS Фаза 5: Catalog CRUD (exposition/archive). CMS Фаза 6: About+Home через settings (WYSIWYG about.history/about.mission, загрузка home.building_image).
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
- SettingsController (app/Http/Controllers/Admin/SettingsController.php) — edit(), update(). DI: ImageUploadService. Flatten вложенных массивов в точечную нотацию. Сохранение через Setting::set(). Загрузка/удаление home.building_image.
- NewsController (app/Http/Controllers/Admin/NewsController.php) — Resource controller (index, create, store, edit, update, destroy). DI: ImageUploadService. Валидация в private method. Soft delete. Image upload/replace/remove.
- ExcursionController (app/Http/Controllers/Admin/ExcursionController.php) — Resource controller (index, create, store, edit, update, destroy). DI: ImageUploadService. Валидация slug unique, WYSIWYG fields. Soft delete.
- UploadController (app/Http/Controllers/Admin/UploadController.php) — image() для загрузки из TinyMCE WYSIWYG. Throttle 30/мин.
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
Layouts: admin.blade.php (panel с sidebar, авторизованные), admin-auth.blade.php (центрированная карточка, guest).
- login.blade.php (67) — @extends admin-auth, email+password, flash errors, link на register
- register.blade.php (78) — @extends admin-auth, name+email+password+confirmation, link на login
- verify.blade.php (118) — @extends admin-auth, input code (6 цифр), resend с таймером 60сек (inline JS + localStorage)
- dashboard.blade.php — @extends admin, quick links (настройки), инфо о пользователе
- settings/edit.blade.php — @extends admin, табы (Контакты/Расписание/О музее/Общие), PUT-форма, multipart. Таб Контакты: address/phone/email/map_id/location_intro (last — multiline текст над адресом в модалке «Как нас найти», nl2br). Таб О музее: about.history, about.mission (wysiwyg). Общие: home.building_image (file upload + preview + remove). TinyMCE подключён.
- news/index.blade.php — @extends admin, таблица (дата, заголовок, статус, действия), пагинация
- news/_form.blade.php — partial (create/edit), поля: title, published_at, text, image (upload + preview JS), is_published (checkbox), remove_image (checkbox)
- news/create.blade.php — @extends admin, @include _form с news=null
- news/edit.blade.php — @extends admin, @include _form с $news
- excursions/index.blade.php — @extends admin, таблица (slug, название, длительность, статус, действия)
- excursions/_form.blade.php — partial (create/edit), поля: slug, title, short_title, short_description, duration_minutes, group_size_min/max, description (wysiwyg), what_you_see (wysiwyg), interesting_facts (wysiwyg), image, is_published, meta_title, meta_description. TinyMCE через @push('scripts').
- excursions/create.blade.php, edit.blade.php — стандартные обёртки
- partials/tinymce.blade.php — TinyMCE 6 self-hosted init с images_upload_handler
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
## Зависимости CMS
- stevebauman/purify ^6.3 — HTMLPurifier, конфиг в config/purify.php
## Требует реализации
- CMS: SEO-настройки (seo.analytics_yandex, seo.analytics_google, seo.robots_txt, seo.verification_yandex, seo.verification_google)
- Управление пользователями
- Профиль / смена пароля
- Роли и права доступа (Policies/Gates)

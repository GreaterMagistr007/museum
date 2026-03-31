---
name: tech_debt
description: Технический долг, безопасность, тесты, известные проблемы
type: project
---
# Технический долг
## Безопасность (критично)
- `.env` содержит закомментированный пароль MySQL: `#DB_PASSWORD=rZ0kR7yK7w` (server/.env:28). .env в .gitignore, но был в git-истории.
- `APP_DEBUG=true` в server/.env:4 — должен быть false в production
- `LOG_LEVEL=debug` в server/.env:20 — в production использовать warning+
- APP_KEY виден в .env — стандартно для Laravel, но .env не должен попадать в git
## Контент (критично)
- Плейсхолдеры изображений на большинстве страниц (`.image-placeholder`)
- Контакты-заглушки: `+7 (3952) XX-XX-XX`, `museum@example.ru`
- Расхождение адресов: модалка tpl-location — "ул. Советская, 176", contacts.blade.php — "ул. Ярослава Гашека, 5"
- anfas.jpg: 7.2 MB — не оптимизирован
## Конфигурация
- `APP_NAME=Laravel` (не изменён)
- `APP_LOCALE=en` при русскоязычном сайте (должно быть ru)
- `APP_TIMEZONE=UTC` (возможно нужен Asia/Irkutsk)
- `MAIL_FROM_ADDRESS="hello@example.com"` — заглушка
- phpunit.xml: тестовое SQLite-подключение закомментировано (строки 26-27)
## Тесты
- Покрытие <5%: 2 заглушки (Feature: GET / -> 200, Unit: true===true)
- Нет тестов для: AuthController, RegisterController, VerificationCodeService, middleware, моделей
## Неиспользуемый код
- Vite настроен (vite.config.js, package.json) но не используется
- resources/css/app.css пустой, resources/js/app.js + bootstrap.js не задействованы
- welcome.blade.php — стандартная заглушка Laravel
## Артефакты
- server.zip (~33 MB) в корне репозитория
- frontend/ — статический HTML-прототип, не используется в production
## Архитектурный долг
- Контент захардкожен в Blade (нет CMS)
- Нет SEO: meta description, OG-теги, Schema.org, sitemap.xml
- Нет формы обратной связи
- Нет версии для слабовидящих (ФЗ-419)
- Нет Docker/CI/CD
- Нет статического анализа (phpstan/psalm)
- Нет ESLint/Prettier для JS/CSS
- Нет package-lock.json (npm lock file)
- cleanup() в VerificationCodeService не вызывается автоматически (нет scheduler)
- Валидация в контроллерах вместо FormRequest классов
- Логи содержат полные stack traces с путями (storage/logs/laravel.log)
## Админка (выявлено 2026-03-31)
- Нет try-catch на Mail::send() в AuthController и RegisterController — при ошибке SMTP пользователь застревает
- Email синхронный (Mail::send вместо Mail::queue)
- User создаётся до верификации кода при регистрации — нет механизма очистки
- Нет аудит-логирования попыток входа
- login.blade.php показывает ссылку на register без учёта ADMIN_REGISTER_ENABLE
## TODO/FIXME
Маркеров TODO, FIXME, HACK, XXX, DEPRECATED в коде не найдено.
## N+1 проблемы
Не применимо — публичные страницы не делают DB-запросов (контент в Blade).

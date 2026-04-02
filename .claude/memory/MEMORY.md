# Museum — индекс памяти
Сайт музея "Иркутское юнкерское училище", г. Иркутск.
Вся память хранится здесь: `.claude/memory/`. Глобальная директория Claude не используется.
## Бизнес-контекст
Информационный сайт военно-исторического музея. Публичные страницы: статические Blade + динамические (news, excursions из БД). Админка с 2FA по email, CMS: Settings (Фаза 1), News CRUD (Фаза 2), Excursions CRUD с TinyMCE WYSIWYG (Фаза 3), Articles CRUD с импортом DOCX (Фаза 4), Catalog CRUD exposition/archive (Фаза 5), About+Home через settings (Фаза 6).
Целевая аудитория: посетители музея, историки, туристы. Конкурентный анализ показал слабые сайты у военно-исторических музеев РФ — возможность стать лучшим в нише.
## Стек
- PHP 8.2+ (установлен 8.5.3), Laravel 11.48
- SQLite (dev), MySQL (prod: museum_db)
- Blade SSR, vanilla CSS (BEM), vanilla JS
- Vite настроен, не используется — assets через `asset()` из `public/`
- PHPUnit 10.5, Laravel Pint, Ignition
- Docker/CI/CD/phpstan/psalm/eslint/prettier — отсутствуют
## Команды запуска
```
cd server
composer install
cp .env.example .env && php artisan key:generate
php artisan migrate
php artisan db:seed          # тестовый пользователь test@example.com
php artisan serve            # dev-сервер http://localhost:8000
php artisan test             # PHPUnit
```
## Переменные окружения (ключевые)
`APP_DEBUG`, `APP_URL`, `DB_CONNECTION` (sqlite|mysql), `DB_HOST/PORT/DATABASE/USERNAME/PASSWORD`, `MAIL_MAILER` (log|smtp), `MAIL_FROM_ADDRESS`, `ADMIN_REGISTER_ENABLE` (false), `LOG_LEVEL`, `SESSION_DRIVER` (database).
## Архитектура и код
- [architecture.md](architecture.md) — структура директорий, роуты, контроллеры, middleware, конфигурация, паттерны
- [models.md](models.md) — модели, миграции, все таблицы БД, seeders, factories
- [services.md](services.md) — VerificationCodeService, VerificationCodeMail, бизнес-логика
- [frontend.md](frontend.md) — CSS (переменные, компоненты, breakpoints), JS (функции), Blade-шаблоны, изображения
- [admin.md](admin.md) — auth flow (2FA), контроллеры, views, безопасность
## Интеграции
- [integration-yandex-maps.md](integration-yandex-maps.md) — iframe Яндекс.Карт (без API-ключа)
Других внешних интеграций нет. Нет HTTP-вызовов к сторонним API из PHP.
## Пропущенные слои
- **api.md** — нет API-роутов, только web
- **Repositories, Jobs, Events, Listeners, Notifications, Commands** — отсутствуют
## Проект
- [project_roadmap.md](project_roadmap.md) — приоритеты развития по итогам конкурентного анализа
- [tech_debt.md](tech_debt.md) — технический долг, безопасность, известные проблемы
## Обратная связь
- [feedback_check_related_code.md](feedback_check_related_code.md) — при изменениях проверять всю цепочку: контроллер, модель, валидацию, миграции, шаблоны
## Ссылки
- [references.md](references.md) — README, competitor-analysis, макеты, ТЗ

---
name: feedback-test-db-safety
description: НИКОГДА не запускать тесты до проверки, что они изолированы от dev/prod БД
metadata:
  type: feedback
---

Перед `php artisan test`, `php artisan migrate:fresh`, `db:seed --force`, `migrate:rollback` и любой командой, потенциально меняющей схему БД, обязательно:
1. Проверить `server/phpunit.xml` — в секции `<php>` должны быть раскомментированы `<env name="DB_CONNECTION" value="sqlite"/>` и `<env name="DB_DATABASE" value=":memory:"/>`. Если закомментированы — НЕ запускать тесты до раскомментирования.
2. На всякий случай сделать копию `server/database/database.sqlite` (`cp .../database.sqlite /tmp/...`).
3. После тестов сверить `sha256sum` файла — должен совпасть с дотестовым.

**Почему:** 2026-05-20 запустил `artisan test` при закомментированных строках — `RefreshDatabase` сделал `migrate:fresh` на dev-БД и снёс весь пользовательский контент (settings, catalog, news и пр.), который пользователь заполнял вручную несколько часов. Восстановление невозможно — пользователь переделывал руками.

**Как применять:**
- Тесты с `use RefreshDatabase;` (а в этом проекте они все такие) выполняют `migrate:fresh` на текущем DB-подключении. Без in-memory переопределения это боевая dev-БД.
- ВСЕ Feature-тесты в `server/tests/Feature/` используют RefreshDatabase — нет «безопасных» тестов, которые можно запустить без проверки.
- См. [[tech_debt]] — раньше эта проблема значилась как пассивный долг, теперь phpunit.xml исправлен (раскомментированы строки 26-27).

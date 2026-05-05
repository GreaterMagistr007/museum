---
name: Deploy museum.in-site.ru
description: Способ прод-деплоя через server/deploy.sh, ограничения сервера, нюансы
type: project
---

Прод: `pkfsb`, пользователь `insite`, директория `/var/www/insite/data/www/museum.in-site.ru/`. Содержимое директории = содержимое `server/` из репозитория.

**Почему деплой так устроен:** Composer и npm на сервере не установлены, vendor/ заливается вручную rsync'ом. Содержимое сервера — без префикса `server/`, поэтому обычный `git pull` положил бы файлы не туда. Решение: bare-клон в `.museum-git/`, `git archive master server/ | tar -x --strip-components=1`.

**How to apply:**
- Скрипт деплоя — `server/deploy.sh`. PHP только `/opt/php85/bin/php` (PHP 8.5), системный `/usr/bin/php` старее.
- Токен GitHub читается из `.env` сервера (переменная `GIT_TOKEN`). В локальном `.env` он не нужен. В `.env.example` оставлен пустой шаблон.
- Деплой берёт только ветку `master`. Чтобы изменения дошли до прода — коммит в `master` + push.
- Bare-клон сидит в `.museum-git/` рядом с `artisan` на сервере; директория добавлена в корневой `.gitignore`.
- Удалённые файлы скрипт сам не сносит (договорённость B1) — после релиза, где удалялись файлы, чистить руками.
- Сидеры не запускаются автоматически (закомментированная строка в скрипте).
- Vite в проекте не используется — `public/build/` синхронизировать не нужно.

**Обновление vendor/** — только при изменении `composer.json`:
```
cd server && composer install --no-dev --optimize-autoloader
rsync -av --delete vendor/ insite@pkfsb:/var/www/insite/data/www/museum.in-site.ru/vendor/
```

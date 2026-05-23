#!/usr/bin/env bash
# Прод-деплой Laravel-проекта museum.in-site.ru.
# Скрипт лежит на сервере в корне директории Laravel-приложения,
# рядом с artisan и .env.
#
# Способ деплоя: bare-клон репозитория в .museum-git/, затем
# git archive ветки master подкаталога server/ — tar в текущую директорию
# с --strip-components=1. Удалённые из git файлы скрипт сам не сносит
# (договорённость B1) — чистить руками после релиза, где это требуется.

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$SCRIPT_DIR"

PHP="/opt/php85/bin/php"
if [[ ! -x "$PHP" ]]; then
    echo "Ошибка: $PHP не найден или не исполняемый. Установлен ли PHP 8.5?" >&2
    exit 1
fi

# Токен GitHub читаем из .env (переменная GIT_TOKEN). .env лежит рядом со скриптом.
ENV_FILE="$SCRIPT_DIR/.env"
if [[ ! -r "$ENV_FILE" ]]; then
    echo "Ошибка: $ENV_FILE не найден или нечитаем." >&2
    exit 1
fi

GIT_TOKEN="$(grep -E '^[[:space:]]*GIT_TOKEN[[:space:]]*=' "$ENV_FILE" \
    | tail -n1 \
    | sed -E 's/^[^=]*=[[:space:]]*//; s/^"(.*)"$/\1/; s/^'\''(.*)'\''$/\1/' \
    | tr -d '[:space:]')"
if [[ -z "$GIT_TOKEN" ]]; then
    echo "Ошибка: переменная GIT_TOKEN пуста или не задана в $ENV_FILE." >&2
    echo "Добавь в .env строку: GIT_TOKEN=ghp_..." >&2
    exit 1
fi

REPO_URL="https://oauth2:${GIT_TOKEN}@github.com/GreaterMagistr007/museum.git"
GIT_DIR="$SCRIPT_DIR/.museum-git"
BRANCH="master"
SUBDIR="server"

# Bare-клон при первом запуске. Origin сразу удаляем, чтобы токен не оседал в config.
if [[ ! -d "$GIT_DIR" ]]; then
    echo "==> Первый запуск: клонирую bare-репозиторий в $GIT_DIR"
    git clone --bare "$REPO_URL" "$GIT_DIR"
    git --git-dir="$GIT_DIR" remote remove origin 2>/dev/null || true
fi

"$PHP" "$SCRIPT_DIR/artisan" down --retry=5 || true
trap '"$PHP" "$SCRIPT_DIR/artisan" up || true' EXIT

echo "==> Получаю $BRANCH с GitHub"
git --git-dir="$GIT_DIR" fetch "$REPO_URL" "+refs/heads/${BRANCH}:refs/heads/${BRANCH}"

echo "==> Раскладываю содержимое $SUBDIR/ ветки $BRANCH в $SCRIPT_DIR"
git --git-dir="$GIT_DIR" archive --format=tar "$BRANCH" "$SUBDIR/" \
    | tar -x --strip-components=1 -C "$SCRIPT_DIR"

echo "==> Чистка bootstrap/cache"
rm -f "$SCRIPT_DIR/bootstrap/cache/packages.php" \
      "$SCRIPT_DIR/bootstrap/cache/services.php" \
      "$SCRIPT_DIR/bootstrap/cache/config.php" \
      "$SCRIPT_DIR/bootstrap/cache/routes-v7.php" \
      "$SCRIPT_DIR/bootstrap/cache/events.php"

"$PHP" "$SCRIPT_DIR/artisan" package:discover --ansi || true

"$PHP" "$SCRIPT_DIR/artisan" migrate --force

# Сидеры по умолчанию НЕ выполняются. Раскомментировать строку ниже при
# необходимости — только если сидеры идемпотентны.
# "$PHP" "$SCRIPT_DIR/artisan" db:seed --force || true

"$PHP" "$SCRIPT_DIR/artisan" config:clear
"$PHP" "$SCRIPT_DIR/artisan" cache:clear
"$PHP" "$SCRIPT_DIR/artisan" route:clear
"$PHP" "$SCRIPT_DIR/artisan" view:clear

"$PHP" "$SCRIPT_DIR/artisan" config:cache
"$PHP" "$SCRIPT_DIR/artisan" route:cache
"$PHP" "$SCRIPT_DIR/artisan" view:cache

"$PHP" "$SCRIPT_DIR/artisan" up
trap - EXIT

echo "==> deploy OK"

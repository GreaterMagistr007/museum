---
name: models
description: Модели, миграции, БД, seeders проекта museum
type: project
---
# Модели и БД
## Модели
`App\Models\User` — стандартная + связь verificationCodes(): HasMany.
Fillable: name, email, password. Casts: password -> hashed.
`App\Models\VerificationCode` — коды 2FA. belongsTo(User). Scope: forUser($userId, $type).
Fillable: user_id, code, type, attempts, expires_at. MAX_ATTEMPTS = 5.
## Миграции
Стандартные Laravel 11 (`0001_01_01_*`): users, password_reset_tokens, sessions, cache, jobs.
Кастомные: `2026_03_19_052010_create_verification_codes_table.php` — verification_codes.
## БД
`.env.example`: SQLite. Prod: MySQL (museum_db).
## Seeders
DatabaseSeeder — один тестовый пользователь (Test User, test@example.com).

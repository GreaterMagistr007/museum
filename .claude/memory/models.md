---
name: models
description: Модели, миграции, все таблицы БД, seeders, factories
type: project
---
# Модели и БД
## БД
Dev: SQLite (`database/database.sqlite`). Prod: MySQL (museum_db, user: museum_insite_us).
## Модели
### User (app/Models/User.php, 49 строк)
Traits: HasFactory, Notifiable. Extends Authenticatable.
Fillable: name, email, password. Hidden: password, remember_token.
Casts: email_verified_at -> datetime, password -> hashed.
Связи: `verificationCodes(): HasMany` (строка 45).
### VerificationCode (app/Models/VerificationCode.php, 72 строки)
Константы: MAX_ATTEMPTS=5, UPDATED_AT=null.
Fillable: user_id, code, type, attempts, expires_at.
Casts: expires_at -> datetime, attempts -> integer.
Связи: `user(): BelongsTo` (строка 36).
Методы:
- `isExpired(): bool` (строка 44) — проверка expires_at
- `hasExceededAttempts(): bool` (строка 52) — attempts >= MAX_ATTEMPTS
- `incrementAttempts(): void` (строка 60)
- `scopeForUser(Builder, int userId, string type): Builder` (строка 68)
## Миграции
### 0001_01_01_000000_create_users_table.php
**users:** id, name, email (unique), email_verified_at (nullable), password, remember_token, timestamps.
**password_reset_tokens:** email (PK), token, created_at (nullable).
**sessions:** id (PK, string), user_id (nullable, index), ip_address(45, nullable), user_agent (text, nullable), payload (longText), last_activity (int, index).
### 0001_01_01_000001_create_cache_table.php
**cache:** key (PK, string), value (mediumText), expiration (int).
**cache_locks:** key (PK, string), owner (string), expiration (int).
### 0001_01_01_000002_create_jobs_table.php
**jobs:** id, queue (index), payload (longText), attempts (tinyInt), reserved_at (nullable), available_at, created_at.
**job_batches:** id (PK, string), name, total_jobs, pending_jobs, failed_jobs, failed_job_ids (longText), options (mediumText, nullable), cancelled_at (nullable), created_at, finished_at (nullable).
**failed_jobs:** id, uuid (unique), connection (text), queue (text), payload (longText), exception (longText), failed_at.
### 2026_03_19_052010_create_verification_codes_table.php
**verification_codes:** id, user_id (FK->users, cascadeOnDelete), code (string, хеш), type (string: login|registration), attempts (tinyInt, default 0), expires_at (timestamp), created_at (nullable). Index: (user_id, type).
## Seeders
DatabaseSeeder — один пользователь: name='Test User', email='test@example.com'. Пароль из factory ('password').
## Factories
UserFactory — name: fake()->name(), email: fake()->unique()->safeEmail(), email_verified_at: now(), password: Hash::make('password') (кешируется), remember_token: Str::random(10).
Модификатор: `unverified()` — email_verified_at=null.

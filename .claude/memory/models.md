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
### News (app/Models/News.php)
Traits: HasFactory, SoftDeletes.
Fillable: title, text, image_path, published_at, is_published, sort_order.
Casts: published_at -> date, is_published -> boolean.
Scopes: `scopePublished(Builder)` — is_published=true, published_at<=now(), orderByDesc published_at.
Accessors: `getImageUrlAttribute()` — Storage::url, `getFormattedDateAttribute()` — d.m.Y.
Boot: forceDeleting -> ImageUploadService::delete(image_path).
### Setting (app/Models/Setting.php)
Таблица `settings` — key-value хранилище настроек сайта с группировкой и кэшированием.
Fillable: key, value, group.
Константы: ALLOWED_KEYS (18 ключей), HTML_KEYS (modals.about, about.history, about.mission).
Статические методы:
- `get(key, default)` — получить значение из кэша
- `set(key, value)` — сохранить (валидация ключа, санитизация HTML через Purify), сброс кэша
- `getGroup(group)` — все настройки группы
- `cached()` — все настройки из Cache::rememberForever('settings')
Группы: contacts, schedule, modals, about, home, seo, general.
## Миграции
### 2026_04_01_000001_create_settings_table.php
**settings:** id, key (string 191, unique), value (text, nullable), group (string 50, default 'general', index), timestamps.
### 2026_04_01_000002_create_news_table.php
**news:** id, title (string 255), text (text), image_path (string 255, nullable), published_at (date, index), is_published (boolean, default true), sort_order (unsignedInt, default 0), timestamps, softDeletes. Index: (is_published, published_at).
## Seeders
DatabaseSeeder — один пользователь + вызов SettingsSeeder + NewsSeeder.
SettingsSeeder — 12 записей: contacts (4), schedule (4), modals (2), general (2). DB::transaction, updateOrCreate.
NewsSeeder — 10 новостей из макета. DB::transaction, updateOrCreate по title.
## Factories
UserFactory — name: fake()->name(), email: fake()->unique()->safeEmail(), email_verified_at: now(), password: Hash::make('password') (кешируется), remember_token: Str::random(10).
Модификатор: `unverified()` — email_verified_at=null.
NewsFactory — title: fake()->sentence(4), text: fake()->paragraph(), published_at: fake()->date(), is_published: true, sort_order: 0.
Модификатор: `unpublished()` — is_published=false.
### Excursion (app/Models/Excursion.php)
Traits: HasFactory, SoftDeletes.
Fillable: slug, title, short_title, short_description, duration_minutes, group_size_min, group_size_max, description, what_you_see, interesting_facts, image_path, is_published, sort_order, meta_title, meta_description.
Casts: is_published -> boolean. RouteKeyName: slug.
Scopes: `scopePublished(Builder)` — is_published=true, orderBy sort_order. `scopeOrdered(Builder)` — orderBy sort_order.
Accessors: `getImageUrlAttribute()` — Storage::url.
Mutators: `setDescriptionAttribute`, `setWhatYouSeeAttribute`, `setInterestingFactsAttribute` — Purify::clean().
Boot: forceDeleting -> ImageUploadService::delete(image_path).
### 2026_04_01_000003_create_excursions_table.php
**excursions:** id, slug (string 191, unique), title (string 255), short_title (string 100, nullable), short_description (text), duration_minutes (unsignedSmallInt), group_size_min (unsignedSmallInt, default 5), group_size_max (unsignedSmallInt, default 25), description (text), what_you_see (text, nullable), interesting_facts (text, nullable), image_path (string 255, nullable), is_published (boolean, default true), sort_order (unsignedInt, default 0), meta_title (string 255, nullable), meta_description (string 500, nullable), timestamps, softDeletes. Index: (is_published, sort_order).
### Article (app/Models/Article.php)
Traits: HasFactory, SoftDeletes.
Fillable: slug, title, content, image_path, parent_id, is_published, sort_order, meta_title, meta_description.
Casts: is_published -> boolean. RouteKeyName: slug.
Связи: `parent(): BelongsTo` (FK parent_id -> articles), `children(): HasMany` (orderBy sort_order).
Scopes: `scopePublished` — is_published=true, `scopeOrdered` — orderBy sort_order, `scopeRoots` — whereNull parent_id.
Mutator: `setContentAttribute` — Purify::clean().
Accessor: `getImageUrlAttribute` — Storage::url.
Boot: forceDeleting -> delete image + nullify children parent_id.
### 2026_04_01_000004_create_articles_table.php
**articles:** id, slug (string 191, unique), title (string 255), content (longText), image_path (string 255, nullable), parent_id (nullable FK->articles, nullOnDelete), is_published (boolean, default true), sort_order (unsignedInt, default 0), meta_title (string 255, nullable), meta_description (string 500, nullable), timestamps, softDeletes. Index: (is_published, sort_order).
## Seeders (обновлено)
DatabaseSeeder — один пользователь + вызов SettingsSeeder + AboutSeeder + NewsSeeder + ExcursionsSeeder + ArticlesSeeder + CatalogSeeder.
AboutSeeder — 2 записи settings: about.history, about.mission. HTML из бывшего хардкода about.blade.php. DB::transaction, updateOrCreate.
ExcursionsSeeder — 6 экскурсий из старых Blade-шаблонов. DB::transaction, updateOrCreate по slug.
ArticlesSeeder — 4 статьи (military-town корень + 3 дочерних: junker-school, infantry-courses, topographic-unit). HTML корневой статьи — inline heredoc. HTML дочерних — из соседних файлов `database/seeders/{slug}.html` через `file_get_contents()` (тексты перенесены из .doc документов музея: ~75/119/68 KB). Подписи к иллюстрациям-плейсхолдерам — `<figure class="image-placeholder"><figcaption>…</figcaption></figure>` (div вырезается Purify, figure[class] разрешён).
## Factories (обновлено)
ExcursionFactory — slug: Str::slug(title), title: fake()->sentence(3), short_title/short_description/description/what_you_see/interesting_facts, duration_minutes: 30-120, group_size_min: 5, group_size_max: 25.
Модификатор: `unpublished()` — is_published=false.
ArticleFactory — slug: Str::slug(title)+random, title: fake()->sentence(4), content: fake()->paragraphs(3) в p-тегах.
Модификатор: `unpublished()` — is_published=false.

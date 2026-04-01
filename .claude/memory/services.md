---
name: services
description: VerificationCodeService, VerificationCodeMail, бизнес-логика авторизации
type: project
---
# Сервисный слой
## VerificationCodeService (app/Services/VerificationCodeService.php, 82 строки)
Stateless-сервис, инжектится через constructor в AuthController и RegisterController.
### generate(User $user, string $type): string (строка 19)
1. Удаляет старые коды для user+type
2. Генерирует 6-значный код: `str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT)`
3. Сохраняет хеш (Hash::make) с expires_at = now()+10мин
4. Возвращает открытый код
### verify(User $user, string $code, string $type): bool (строка 43)
1. Ищет код по user+type через scope forUser
2. Нет записи -> false
3. Истёк срок -> удаляет, false
4. Превышены попытки (>=5) -> удаляет, false
5. Hash::check неверный -> incrementAttempts, false
6. Верный -> удаляет запись, true
### cleanup(): void (строка 78)
Удаляет все просроченные коды (expires_at < now).
Не вызывается автоматически (нет scheduler/cron).
## VerificationCodeMail (app/Mail/VerificationCodeMail.php, 43 строки)
Mailable + Queueable + SerializesModels.
Props: `code` (string, readonly), `type` (string: login|registration, readonly).
Subject: "Код подтверждения -- Музей 'Иркутское юнкерское училище'" (строка 29).
View: `emails.verification-code` (строка 39).
## ImageUploadService (app/Services/ImageUploadService.php)
Сервис загрузки/удаления/замены изображений. Хранение на default disk (local) в `public/uploads/{directory}/`.
Допустимые директории: news, excursions, articles, catalog, content, settings.
Методы:
- `upload(UploadedFile, string directory): string` — UUID-имя, storeAs, возвращает путь
- `delete(?string path): void` — удаление если существует
- `replace(?string oldPath, UploadedFile, string directory): string` — delete + upload
## Отсутствующие компоненты
Repositories, Actions, Jobs, Events, Listeners, Notifications, Artisan Commands — нет.
Очереди (QUEUE_CONNECTION=database) настроены, но не используются — mail отправляется синхронно.

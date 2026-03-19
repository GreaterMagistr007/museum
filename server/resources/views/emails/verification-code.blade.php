<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
</head>
<body style="font-family: Georgia, 'Times New Roman', serif; color: #3E2218; background-color: #F5EDE2; padding: 40px 20px; margin: 0;">
    <div style="max-width: 480px; margin: 0 auto; background-color: #FFFFFF; border-radius: 10px; padding: 40px; box-shadow: 0 3px 10px rgba(0,0,0,0.15);">
        <h2 style="color: #8B1A1A; margin: 0 0 16px; font-size: 20px;">
            Музей «Иркутское юнкерское училище»
        </h2>

        <p style="margin: 0 0 12px; line-height: 1.5;">
            @if ($type === 'registration')
                Для завершения регистрации введите код подтверждения:
            @else
                Для входа в панель управления введите код подтверждения:
            @endif
        </p>

        <div style="text-align: center; margin: 24px 0;">
            <span style="display: inline-block; font-size: 36px; font-weight: bold; letter-spacing: 8px; color: #8B1A1A; background-color: #EDDCC8; padding: 16px 32px; border-radius: 10px;">
                {{ $code }}
            </span>
        </div>

        <p style="margin: 0 0 8px; line-height: 1.5; color: #5A3A2A;">
            Код действителен в течение 10 минут.
        </p>

        <p style="margin: 0; line-height: 1.5; color: #5A3A2A; font-size: 14px;">
            Если вы не запрашивали этот код, просто проигнорируйте это письмо.
        </p>
    </div>
</body>
</html>

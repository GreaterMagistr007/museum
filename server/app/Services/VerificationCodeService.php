<?php

namespace App\Services;

use App\Models\User;
use App\Models\VerificationCode;
use Illuminate\Support\Facades\Hash;

class VerificationCodeService
{
    /**
     * Генерирует новый код верификации.
     *
     * Удаляет старые коды для данного пользователя и типа,
     * создаёт новый 6-значный код, сохраняет его хеш в БД.
     *
     * @return string Открытый (нехешированный) код
     */
    public function generate(User $user, string $type): string
    {
        // Удаляем все предыдущие коды для данного пользователя и типа
        VerificationCode::forUser($user->id, $type)->delete();

        // Генерируем 6-значный числовой код
        $plainCode = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        VerificationCode::create([
            'user_id' => $user->id,
            'code' => Hash::make($plainCode),
            'type' => $type,
            'attempts' => 0,
            'expires_at' => now()->addMinutes(10),
        ]);

        return $plainCode;
    }

    /**
     * Проверяет код верификации.
     *
     * @return bool true — код верный и действителен, false — неверный/истёк/превышены попытки
     */
    public function verify(User $user, string $code, string $type): bool
    {
        $verificationCode = VerificationCode::forUser($user->id, $type)->first();

        if (! $verificationCode) {
            return false;
        }

        // Проверка истечения срока
        if ($verificationCode->isExpired()) {
            $verificationCode->delete();
            return false;
        }

        // Проверка превышения попыток
        if ($verificationCode->hasExceededAttempts()) {
            $verificationCode->delete();
            return false;
        }

        // Проверка кода
        if (! Hash::check($code, $verificationCode->code)) {
            $verificationCode->incrementAttempts();
            return false;
        }

        // Код верный — удаляем запись
        $verificationCode->delete();

        return true;
    }

    /**
     * Удаляет все просроченные коды верификации.
     */
    public function cleanup(): void
    {
        VerificationCode::where('expires_at', '<', now())->delete();
    }
}

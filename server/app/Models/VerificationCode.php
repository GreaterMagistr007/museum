<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VerificationCode extends Model
{
    /** Без updated_at — в таблице его нет. */
    const UPDATED_AT = null;

    /** Максимальное количество попыток ввода кода. */
    const MAX_ATTEMPTS = 5;

    protected $fillable = [
        'user_id',
        'code',
        'type',
        'attempts',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'attempts' => 'integer',
        ];
    }

    /**
     * Пользователь, которому принадлежит код.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Проверяет, истёк ли срок действия кода.
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Проверяет, превышено ли максимальное количество попыток.
     */
    public function hasExceededAttempts(): bool
    {
        return $this->attempts >= self::MAX_ATTEMPTS;
    }

    /**
     * Увеличивает счётчик попыток на 1.
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Область запроса: фильтрация по пользователю и типу.
     */
    public function scopeForUser(Builder $query, int $userId, string $type): Builder
    {
        return $query->where('user_id', $userId)->where('type', $type);
    }
}

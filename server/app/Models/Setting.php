<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * Модель настроек сайта.
 *
 * Хранит пары ключ-значение с группировкой и кэшированием.
 * HTML-поля санитизируются через HTMLPurifier (stevebauman/purify).
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    /** Допустимые ключи настроек */
    public const ALLOWED_KEYS = [
        'contacts.address', 'contacts.phone', 'contacts.email', 'contacts.map_id',
        'schedule.weekdays', 'schedule.saturday', 'schedule.sunday', 'schedule.note',
        'modals.location_address',
        'about.history', 'about.mission',
        'home.building_image',
        'seo.analytics_yandex', 'seo.analytics_google', 'seo.robots_txt',
        'seo.verification_yandex', 'seo.verification_google',
        'general.site_title', 'general.site_subtitle', 'general.footer_text',
    ];

    /** HTML-поля, которые нужно санитизировать через Purify */
    public const HTML_KEYS = ['about.history', 'about.mission'];

    /**
     * Получить значение настройки по ключу.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return static::cached()[$key] ?? $default;
    }

    /**
     * Установить значение настройки.
     *
     * @throws \InvalidArgumentException если ключ не в списке допустимых
     */
    public static function set(string $key, ?string $value): void
    {
        if (!in_array($key, static::ALLOWED_KEYS)) {
            throw new \InvalidArgumentException("Недопустимый ключ настройки: {$key}");
        }

        // Санитизация HTML-полей
        if (in_array($key, static::HTML_KEYS) && $value !== null) {
            $value = \Stevebauman\Purify\Facades\Purify::clean($value);
        }

        // Определяем группу из ключа (часть до первой точки)
        $group = explode('.', $key, 2)[0];

        static::updateOrCreate(['key' => $key], ['value' => $value, 'group' => $group]);
        Cache::forget('settings');
    }

    /**
     * Получить все настройки определённой группы.
     */
    public static function getGroup(string $group): array
    {
        $all = static::cached();
        $prefix = $group . '.';
        $result = [];

        foreach ($all as $k => $v) {
            if (str_starts_with($k, $prefix)) {
                $result[$k] = $v;
            }
        }

        return $result;
    }

    /**
     * Получить все настройки из кэша.
     */
    public static function cached(): array
    {
        return Cache::rememberForever('settings', function () {
            return static::pluck('value', 'key')->toArray();
        });
    }
}

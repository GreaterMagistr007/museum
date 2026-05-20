<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Удаляет устаревшую настройку modals.about — кнопка «О музее» на главной
     * больше не открывает модалку, а ведёт на страницу /about.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'modals.about')->delete();
        Cache::forget('settings');
    }

    /**
     * Откат невозможен — значение настройки утрачивается. Оставляем пустым.
     */
    public function down(): void
    {
        //
    }
};

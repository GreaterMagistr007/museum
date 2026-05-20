<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Удаляет неиспользуемую настройку modals.location_address — она нигде
     * не выводилась в Blade. Текст для модалки «Как нас найти» теперь
     * хранится в contacts.location_intro.
     */
    public function up(): void
    {
        DB::table('settings')->where('key', 'modals.location_address')->delete();
        Cache::forget('settings');
    }

    public function down(): void
    {
        //
    }
};

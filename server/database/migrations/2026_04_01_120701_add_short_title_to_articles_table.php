<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Добавить short_title и nav_title для отображения на главной и в навигации.
     */
    public function up(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->string('short_title', 100)->nullable()->after('title');
            $table->string('nav_title', 100)->nullable()->after('short_title');
        });
    }

    /**
     * Откатить миграцию.
     */
    public function down(): void
    {
        Schema::table('articles', function (Blueprint $table) {
            $table->dropColumn(['short_title', 'nav_title']);
        });
    }
};

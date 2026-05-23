<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Переименовать duration_minutes в duration и сменить тип на строку.
     */
    public function up(): void
    {
        Schema::table('excursions', function (Blueprint $table) {
            $table->renameColumn('duration_minutes', 'duration');
        });

        Schema::table('excursions', function (Blueprint $table) {
            $table->string('duration', 255)->nullable()->change();
        });
    }

    /**
     * Откат: вернуть unsignedSmallInteger и старое имя столбца.
     */
    public function down(): void
    {
        Schema::table('excursions', function (Blueprint $table) {
            $table->unsignedSmallInteger('duration')->nullable()->change();
        });

        Schema::table('excursions', function (Blueprint $table) {
            $table->renameColumn('duration', 'duration_minutes');
        });
    }
};

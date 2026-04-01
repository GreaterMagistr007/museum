<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Таблица экскурсий.
     */
    public function up(): void
    {
        Schema::create('excursions', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 191)->unique();
            $table->string('title', 255);
            $table->string('short_title', 100)->nullable();
            $table->text('short_description');
            $table->unsignedSmallInteger('duration_minutes');
            $table->unsignedSmallInteger('group_size_min')->default(5);
            $table->unsignedSmallInteger('group_size_max')->default(25);
            $table->text('description');
            $table->text('what_you_see')->nullable();
            $table->text('interesting_facts')->nullable();
            $table->string('image_path', 255)->nullable();
            $table->boolean('is_published')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('meta_title', 255)->nullable();
            $table->string('meta_description', 500)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['is_published', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('excursions');
    }
};

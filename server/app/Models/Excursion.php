<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Stevebauman\Purify\Facades\Purify;

/**
 * Модель экскурсии.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string|null $short_title
 * @property string $short_description
 * @property int $duration_minutes
 * @property int $group_size_min
 * @property int $group_size_max
 * @property string $description
 * @property string|null $what_you_see
 * @property string|null $interesting_facts
 * @property string|null $image_path
 * @property bool $is_published
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string|null $image_url
 */
class Excursion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'short_title',
        'short_description',
        'duration_minutes',
        'group_size_min',
        'group_size_max',
        'description',
        'what_you_see',
        'interesting_facts',
        'image_path',
        'is_published',
        'sort_order',
        'meta_title',
        'meta_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Ключ маршрутизации — slug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * Только опубликованные, отсортированные по sort_order.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->orderBy('sort_order');
    }

    /**
     * Сортировка по sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * URL изображения через Storage.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (! $this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }

    /**
     * Санитизация short_title: допускаются только <br> и HTML-сущности.
     */
    public function setShortTitleAttribute(?string $value): void
    {
        $this->attributes['short_title'] = $value ? strip_tags($value, '<br>') : null;
    }

    /**
     * Санитизация HTML-контента описания.
     */
    public function setDescriptionAttribute(?string $value): void
    {
        $this->attributes['description'] = $value ? Purify::clean($value) : null;
    }

    /**
     * Санитизация HTML-контента «что вы увидите».
     */
    public function setWhatYouSeeAttribute(?string $value): void
    {
        $this->attributes['what_you_see'] = $value ? Purify::clean($value) : null;
    }

    /**
     * Санитизация HTML-контента «интересные факты».
     */
    public function setInterestingFactsAttribute(?string $value): void
    {
        $this->attributes['interesting_facts'] = $value ? Purify::clean($value) : null;
    }

    /**
     * При полном удалении — удалить файл изображения.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::forceDeleting(function (Excursion $model) {
            app(ImageUploadService::class)->delete($model->image_path);
        });
    }
}

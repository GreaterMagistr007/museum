<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;
use Stevebauman\Purify\Facades\Purify;

/**
 * Модель статьи (раздел "Военный городок" и дочерние).
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property string $content
 * @property string|null $image_path
 * @property int|null $parent_id
 * @property bool $is_published
 * @property int $sort_order
 * @property string|null $meta_title
 * @property string|null $meta_description
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string|null $image_url
 * @property-read Article|null $parent
 * @property-read \Illuminate\Database\Eloquent\Collection<int, Article> $children
 */
class Article extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'slug',
        'title',
        'content',
        'image_path',
        'parent_id',
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
     * Родительская статья.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Article::class, 'parent_id');
    }

    /**
     * Дочерние статьи.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Article::class, 'parent_id')->orderBy('sort_order');
    }

    /**
     * Только опубликованные статьи.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true);
    }

    /**
     * Сортировка по sort_order.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order');
    }

    /**
     * Только корневые статьи (без родителя).
     */
    public function scopeRoots(Builder $query): Builder
    {
        return $query->whereNull('parent_id');
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
     * Санитизация HTML-контента при сохранении.
     */
    public function setContentAttribute(string $value): void
    {
        $this->attributes['content'] = Purify::clean($value);
    }

    /**
     * При полном удалении — удалить изображение и обнулить parent_id у дочерних.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::forceDeleting(function (Article $model) {
            // Удаление изображения
            app(ImageUploadService::class)->delete($model->image_path);

            // Обнуление parent_id у дочерних статей (ON DELETE SET NULL через FK)
            // Дополнительно обнуляем через Eloquent, чтобы сработали observer-хуки если появятся
            $model->children()->update(['parent_id' => null]);
        });
    }
}

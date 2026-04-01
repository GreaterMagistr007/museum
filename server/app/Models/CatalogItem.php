<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Элемент каталога (экспозиция / архив).
 *
 * @property int $id
 * @property string $type
 * @property string $title
 * @property string $description
 * @property string|null $image_path
 * @property string|null $link_url
 * @property bool $is_published
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read string|null $image_url
 */
class CatalogItem extends Model
{
    protected $fillable = [
        'title',
        'description',
        'image_path',
        'link_url',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'is_published' => 'boolean',
    ];

    /**
     * Только элементы экспозиции.
     */
    public function scopeExposition(Builder $query): Builder
    {
        return $query->where('type', 'exposition');
    }

    /**
     * Только элементы архива.
     */
    public function scopeArchive(Builder $query): Builder
    {
        return $query->where('type', 'archive');
    }

    /**
     * Опубликованные элементы, отсортированные по sort_order.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }

    /**
     * Фильтр по типу.
     */
    public function scopeOfType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * URL изображения через Storage.
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image_path) {
            return null;
        }

        return Storage::url($this->image_path);
    }

    /**
     * При удалении — удалить файл изображения.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::deleting(function (CatalogItem $model) {
            app(ImageUploadService::class)->delete($model->image_path);
        });
    }
}

<?php

namespace App\Models;

use App\Services\ImageUploadService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Модель новости.
 *
 * @property int $id
 * @property string $title
 * @property string $text
 * @property string|null $image_path
 * @property \Illuminate\Support\Carbon $published_at
 * @property bool $is_published
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property \Illuminate\Support\Carbon|null $deleted_at
 * @property-read string|null $image_url
 * @property-read string $formatted_date
 */
class News extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'title',
        'text',
        'image_path',
        'published_at',
        'is_published',
        'sort_order',
    ];

    protected $casts = [
        'published_at' => 'date',
        'is_published' => 'boolean',
    ];

    /**
     * Только опубликованные новости с датой не позднее текущей.
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query
            ->where('is_published', true)
            ->where('published_at', '<=', now())
            ->orderByDesc('published_at');
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
     * Дата в формате dd.mm.YYYY.
     */
    public function getFormattedDateAttribute(): string
    {
        return $this->published_at->format('d.m.Y');
    }

    /**
     * При полном удалении — удалить файл изображения.
     */
    protected static function boot(): void
    {
        parent::boot();

        static::forceDeleting(function (News $model) {
            app(ImageUploadService::class)->delete($model->image_path);
        });
    }
}

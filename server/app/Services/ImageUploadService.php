<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Сервис загрузки изображений.
 */
class ImageUploadService
{
    /** Допустимые директории для загрузки */
    private const ALLOWED_DIRS = ['news', 'excursions', 'articles', 'catalog', 'content', 'settings'];

    /**
     * Загрузить файл в указанную директорию.
     */
    public function upload(UploadedFile $file, string $directory): string
    {
        if (!in_array($directory, self::ALLOWED_DIRS)) {
            throw new \InvalidArgumentException("Недопустимая директория: {$directory}");
        }

        $filename = Str::uuid() . '.' . $file->extension();

        return $file->storeAs('public/uploads/' . $directory, $filename);
    }

    /**
     * Удалить файл по пути.
     */
    public function delete(?string $path): void
    {
        if ($path && Storage::exists($path)) {
            Storage::delete($path);
        }
    }

    /**
     * Заменить старый файл новым.
     */
    public function replace(?string $oldPath, UploadedFile $file, string $directory): string
    {
        $this->delete($oldPath);

        return $this->upload($file, $directory);
    }
}

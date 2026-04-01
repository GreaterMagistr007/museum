<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Контроллер загрузки изображений (для TinyMCE WYSIWYG).
 */
class UploadController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageUploadService,
    ) {}

    /**
     * Загрузка изображения из WYSIWYG-редактора.
     */
    public function image(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'image', 'mimes:jpeg,png,webp,gif', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
        ]);

        $path = $this->imageUploadService->upload($request->file('file'), 'content');

        return response()->json(['location' => Storage::url($path)]);
    }
}

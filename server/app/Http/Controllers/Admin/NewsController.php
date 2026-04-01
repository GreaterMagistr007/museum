<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD-контроллер новостей в админ-панели.
 */
class NewsController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * Список новостей.
     */
    public function index(): View
    {
        $news = News::latest('published_at')->paginate(15);

        return view('admin.news.index', compact('news'));
    }

    /**
     * Форма создания новости.
     */
    public function create(): View
    {
        return view('admin.news.create');
    }

    /**
     * Сохранение новой новости.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $data = [
            'title' => $validated['title'],
            'text' => $validated['text'],
            'published_at' => $validated['published_at'],
            'is_published' => $request->boolean('is_published'),
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->upload($request->file('image'), 'news');
        }

        News::create($data);

        return redirect()->route('admin.news.index')->with('success', 'Новость создана.');
    }

    /**
     * Форма редактирования новости.
     */
    public function edit(News $news): View
    {
        return view('admin.news.edit', compact('news'));
    }

    /**
     * Обновление новости.
     */
    public function update(Request $request, News $news): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $data = [
            'title' => $validated['title'],
            'text' => $validated['text'],
            'published_at' => $validated['published_at'],
            'is_published' => $request->boolean('is_published'),
        ];

        // Загрузка нового изображения
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace($news->image_path, $request->file('image'), 'news');
        } elseif ($request->boolean('remove_image')) {
            // Удаление текущего изображения
            $this->imageService->delete($news->image_path);
            $data['image_path'] = null;
        }

        $news->update($data);

        return redirect()->route('admin.news.index')->with('success', 'Новость обновлена.');
    }

    /**
     * Удаление новости (soft delete).
     */
    public function destroy(News $news): RedirectResponse
    {
        $news->delete();

        return redirect()->route('admin.news.index')->with('success', 'Новость удалена.');
    }

    /**
     * Правила валидации для store и update.
     */
    private function validationRules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'text' => ['required', 'string'],
            'published_at' => ['required', 'date'],
            'is_published' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }
}

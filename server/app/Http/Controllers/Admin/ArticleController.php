<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Services\ArticleImportService;
use App\Services\ImageUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CRUD-контроллер статей в админ-панели.
 */
class ArticleController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
        private readonly ArticleImportService $importService,
    ) {}

    /**
     * Список статей (корневые + дочерние).
     */
    public function index(): View
    {
        $articles = Article::withTrashed()
            ->roots()
            ->with(['children' => fn ($q) => $q->withTrashed()->ordered()])
            ->ordered()
            ->get();

        return view('admin.articles.index', compact('articles'));
    }

    /**
     * Форма создания статьи.
     */
    public function create(): View
    {
        $parents = Article::whereNull('parent_id')->ordered()->get();

        return view('admin.articles.create', compact('parents'));
    }

    /**
     * Сохранение новой статьи.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $data = $this->extractData($request, $validated);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->upload($request->file('image'), 'articles');
        }

        Article::create($data);

        return redirect()->route('admin.articles.index')->with('success', 'Статья создана.');
    }

    /**
     * Форма редактирования статьи.
     */
    public function edit(Article $article): View
    {
        // Исключить саму статью и её потомков из списка возможных родителей
        $parents = Article::whereNull('parent_id')
            ->where('id', '!=', $article->id)
            ->ordered()
            ->get();

        return view('admin.articles.edit', compact('article', 'parents'));
    }

    /**
     * Обновление статьи.
     */
    public function update(Request $request, Article $article): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($article));

        $data = $this->extractData($request, $validated);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace($article->image_path, $request->file('image'), 'articles');
        } elseif ($request->boolean('remove_image')) {
            $this->imageService->delete($article->image_path);
            $data['image_path'] = null;
        }

        $article->update($data);

        return redirect()->route('admin.articles.index')->with('success', 'Статья обновлена.');
    }

    /**
     * Soft delete статьи.
     */
    public function destroy(Article $article): RedirectResponse
    {
        $article->delete();

        return redirect()->route('admin.articles.index')->with('success', 'Статья удалена.');
    }

    /**
     * Импорт статьи из DOCX-файла (AJAX).
     * Возвращает извлечённый HTML-контент.
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:docx', 'max:10240'],
        ]);

        try {
            $html = $this->importService->importFromDocx($request->file('file'));
        } catch (\Exception $e) {
            return response()->json(['error' => 'Не удалось обработать файл: ' . $e->getMessage()], 422);
        }

        return response()->json(['html' => $html]);
    }

    /**
     * Извлечь данные из валидированного запроса.
     */
    private function extractData(Request $request, array $validated): array
    {
        return [
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'content' => $validated['content'],
            'parent_id' => $validated['parent_id'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'sort_order' => $validated['sort_order'] ?? 0,
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ];
    }

    /**
     * Правила валидации для store/update.
     */
    private function validationRules(?Article $article = null): array
    {
        return [
            'slug' => ['required', 'string', 'max:191', 'alpha_dash', Rule::unique('articles')->ignore($article?->id)],
            'title' => ['required', 'string', 'max:255'],
            'content' => ['required', 'string'],
            'parent_id' => ['nullable', 'integer', 'exists:articles,id'],
            'is_published' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];
    }
}

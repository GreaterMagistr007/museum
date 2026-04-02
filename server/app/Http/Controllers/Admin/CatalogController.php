<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CatalogItem;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * CRUD-контроллер каталога (экспозиция / архив) в админ-панели.
 */
class CatalogController extends Controller
{
    /** Допустимые типы каталога */
    private const ALLOWED_TYPES = ['exposition', 'archive'];

    /** Подписи для UI */
    private const LABELS = [
        'exposition' => ['title' => 'Экспозиция', 'singular' => 'элемент экспозиции'],
        'archive' => ['title' => 'Архив', 'singular' => 'элемент архива'],
    ];

    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * Список элементов каталога.
     */
    public function index(string $type): View
    {
        $this->validateType($type);

        $items = CatalogItem::ofType($type)->orderBy('sort_order')->get();
        $labels = self::LABELS[$type];

        return view('admin.catalog.index', compact('items', 'type', 'labels'));
    }

    /**
     * Форма создания элемента.
     */
    public function create(string $type): View
    {
        $this->validateType($type);

        $labels = self::LABELS[$type];

        return view('admin.catalog.create', compact('type', 'labels'));
    }

    /**
     * Сохранение нового элемента.
     */
    public function store(Request $request, string $type): RedirectResponse
    {
        $this->validateType($type);

        $validated = $request->validate($this->validationRules());

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'link_url' => $validated['link_url'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ];

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->upload($request->file('image'), 'catalog');
        }

        $item = new CatalogItem($data);
        $item->type = $type;
        $item->save();

        $labels = self::LABELS[$type];

        return redirect()
            ->route('admin.catalog.index', $type)
            ->with('success', ucfirst($labels['singular']) . ' создан.');
    }

    /**
     * Форма редактирования элемента.
     */
    public function edit(string $type, CatalogItem $catalogItem): View
    {
        $this->validateType($type);

        $labels = self::LABELS[$type];

        return view('admin.catalog.edit', compact('catalogItem', 'type', 'labels'));
    }

    /**
     * Обновление элемента.
     */
    public function update(Request $request, string $type, CatalogItem $catalogItem): RedirectResponse
    {
        $this->validateType($type);

        $validated = $request->validate($this->validationRules());

        $data = [
            'title' => $validated['title'],
            'description' => $validated['description'],
            'link_url' => $validated['link_url'] ?? null,
            'is_published' => $request->boolean('is_published'),
        ];

        // Загрузка нового изображения
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace($catalogItem->image_path, $request->file('image'), 'catalog');
        } elseif ($request->boolean('remove_image')) {
            $this->imageService->delete($catalogItem->image_path);
            $data['image_path'] = null;
        }

        $catalogItem->update($data);

        $labels = self::LABELS[$type];

        return redirect()
            ->route('admin.catalog.index', $type)
            ->with('success', ucfirst($labels['singular']) . ' обновлён.');
    }

    /**
     * Удаление элемента.
     */
    public function destroy(string $type, CatalogItem $catalogItem): RedirectResponse
    {
        $this->validateType($type);

        $catalogItem->delete();

        $labels = self::LABELS[$type];

        return redirect()
            ->route('admin.catalog.index', $type)
            ->with('success', ucfirst($labels['singular']) . ' удалён.');
    }

    /**
     * Проверка допустимости типа каталога.
     */
    private function validateType(string $type): void
    {
        if (!in_array($type, self::ALLOWED_TYPES, true)) {
            abort(404);
        }
    }

    /**
     * Правила валидации для store и update.
     */
    private function validationRules(): array
    {
        return [
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120', 'dimensions:max_width=4096,max_height=4096'],
            'remove_image' => ['sometimes', 'boolean'],
            'link_url' => ['nullable', 'url', 'max:255', 'regex:/^https?:\/\//i'],
            'is_published' => ['sometimes', 'boolean'],
        ];
    }
}

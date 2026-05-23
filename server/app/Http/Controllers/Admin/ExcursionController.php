<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Excursion;
use App\Services\ImageUploadService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * CRUD-контроллер экскурсий в админ-панели.
 */
class ExcursionController extends Controller
{
    public function __construct(
        private readonly ImageUploadService $imageService,
    ) {}

    /**
     * Список экскурсий.
     */
    public function index(): View
    {
        $excursions = Excursion::ordered()->get();

        return view('admin.excursions.index', compact('excursions'));
    }

    /**
     * Форма создания экскурсии.
     */
    public function create(): View
    {
        return view('admin.excursions.create');
    }

    /**
     * Сохранение новой экскурсии.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->validationRules());

        $data = $this->extractData($request, $validated);
        $data['slug'] = $this->generateUniqueSlug($validated['title']);

        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->upload($request->file('image'), 'excursions');
        }

        Excursion::create($data);

        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия создана.');
    }

    /**
     * Форма редактирования экскурсии.
     */
    public function edit(Excursion $excursion): View
    {
        return view('admin.excursions.edit', compact('excursion'));
    }

    /**
     * Обновление экскурсии.
     */
    public function update(Request $request, Excursion $excursion): RedirectResponse
    {
        $validated = $request->validate($this->validationRules($excursion));

        $data = $this->extractData($request, $validated);

        // Загрузка нового изображения
        if ($request->hasFile('image')) {
            $data['image_path'] = $this->imageService->replace($excursion->image_path, $request->file('image'), 'excursions');
        } elseif ($request->boolean('remove_image')) {
            $this->imageService->delete($excursion->image_path);
            $data['image_path'] = null;
        }

        $excursion->update($data);

        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия обновлена.');
    }

    /**
     * Удаление экскурсии (soft delete).
     */
    public function destroy(Excursion $excursion): RedirectResponse
    {
        $excursion->delete();

        return redirect()->route('admin.excursions.index')->with('success', 'Экскурсия удалена.');
    }

    /**
     * Генерация уникального slug на основе названия.
     */
    private function generateUniqueSlug(string $title): string
    {
        $slug = Str::slug($title);
        $original = $slug;
        $counter = 2;

        while (Excursion::withTrashed()->where('slug', $slug)->exists()) {
            $slug = $original . '-' . $counter;
            $counter++;
        }

        return $slug;
    }

    /**
     * Извлечь данные из запроса.
     */
    private function extractData(Request $request, array $validated): array
    {
        $data = [
            'title' => $validated['title'],
            'short_title' => $validated['short_title'] ?? null,
            'short_description' => $validated['short_description'],
            'duration' => $validated['duration'] ?? null,
            'group_size_min' => $validated['group_size_min'],
            'group_size_max' => $validated['group_size_max'],
            'description' => $validated['description'],
            'what_you_see' => $validated['what_you_see'] ?? null,
            'interesting_facts' => $validated['interesting_facts'] ?? null,
            'is_published' => $request->boolean('is_published'),
            'meta_title' => $validated['meta_title'] ?? null,
            'meta_description' => $validated['meta_description'] ?? null,
        ];

        if (isset($validated['slug'])) {
            $data['slug'] = $validated['slug'];
        }

        return $data;
    }

    /**
     * Правила валидации для store и update.
     */
    private function validationRules(?Excursion $excursion = null): array
    {
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'short_title' => ['nullable', 'string', 'max:100'],
            'short_description' => ['required', 'string'],
            'duration' => ['nullable', 'string', 'max:255'],
            'group_size_min' => ['nullable', 'integer', 'min:1'],
            'group_size_max' => ['nullable', 'integer', 'min:1', 'gte:group_size_min'],
            'description' => ['nullable', 'string'],
            'what_you_see' => ['nullable', 'string'],
            'interesting_facts' => ['nullable', 'string'],
            'is_published' => ['sometimes', 'boolean'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,webp', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:500'],
        ];

        // При редактировании slug обязателен и уникален
        if ($excursion) {
            $rules['slug'] = ['required', 'string', 'max:191', 'alpha_dash', Rule::unique('excursions')->ignore($excursion->id)];
        }

        return $rules;
    }
}
